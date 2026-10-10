<?php

declare(strict_types=1);

namespace App\Support\Datos;

use Carbon\CarbonImmutable;
use RuntimeException;
use ZipArchive;

/**
 * Escribe lo que el sitio tiene de una persona como un ZIP con dos copias de lo
 * mismo: `datos.txt`, para leerlo, y `datos.json`, para llevárselo a otro sistema.
 *
 * El ZIP es lo que se manda por correo. Puede protegerse con una clave (AES-256),
 * que se le da a la persona por otro medio: el correo con los datos y la clave en
 * el mismo mensaje no protege de nada.
 */
final class ArchivoDeEntrega
{
    /** Cómo se llama cada dato en el TXT. Lo que no esté aquí sale con su propio nombre. */
    private const ETIQUETAS = [
        'nombre' => 'Nombre',
        'celular' => 'Celular',
        'correo' => 'Correo',
        'lotes' => 'Propiedades',
        'calle' => 'Calle',
        'numero_oficial' => 'Número oficial',
        'manzana' => 'Manzana',
        'lote' => 'Lote',
        'tipo_de_propiedad' => 'Tipo de propiedad',
        'contactos_adicionales' => 'Contactos adicionales',
        'contacto_de_emergencia' => 'Contacto de emergencia',
        'acepto_el_aviso_de_privacidad_el' => 'Aceptó el Aviso de Privacidad el',
        'version_del_aviso' => 'Versión del Aviso',
        'correo_verificado_el' => 'Correo verificado el',
        'correo_verificado_con' => 'Correo verificado con',
        'celular_verificado_el' => 'Celular verificado el',
        'validado_por_la_administracion_el' => 'Validado por la Administración el',
        'nota_de_la_validacion' => 'Nota de la validación',
        'registrado_el' => 'Registrado el',
        'actualizado_el' => 'Actualizado el',
        'como' => 'Como',
        'folio' => 'Folio',
        'domicilio' => 'Domicilio',
        'anotada_el' => 'Anotada el',
        'actualizada_el' => 'Actualizada el',
        'nombre_con_el_que_firmo' => 'Nombre con el que firmó',
        'comentario' => 'Comentario',
        'visibilidad' => 'Visibilidad',
        'estado' => 'Estado',
        'escrito_el' => 'Escrito el',
        'para' => 'Para',
        'pedido_el' => 'Pedido el',
        'verificado_el' => 'Verificado el',
    ];

    private const SIN_PAGOS = 'Este sitio todavía no registra pagos, así que no hay pagos suyos que entregar.';

    private const NO_INCLUYE = 'No incluye los registros técnicos del servidor (como la dirección IP con la que se '
        .'conectó), que se guardan solo para proteger el sitio de abusos y se borran por su cuenta.';

    /**
     * El texto legible.
     */
    public function texto(DatosDeUnaPersona $persona, ?CarbonImmutable $ahora = null): string
    {
        $ahora ??= CarbonImmutable::now();
        $anos = (int) config('contenido.legal.conservacion_anos');

        $lineas = [
            'DATOS PERSONALES EN VISTA ALTA',
            'Generado el '.$this->fechaLegible($ahora->toIso8601String()),
            '',
            'Persona identificada por su '.($persona->tipo === 'correo' ? 'correo' : 'celular').': '.$persona->valor,
        ];

        $extra = $persona->tipo === 'correo' ? $persona->telefonos : $persona->correos;
        $extra = array_values(array_diff($extra, [$persona->valor]));

        if ($extra !== []) {
            $lineas[] = 'También se incluye lo asociado a: '.implode(', ', $extra)
                .' (se verificó junto con el dato anterior).';
        }

        $lineas[] = "Conservamos estos datos hasta {$anos} años después de la última vez que se actualizaron o usaron.";

        foreach ($persona->fuentes as $fuente) {
            $lineas[] = '';
            $lineas[] = '== '.$fuente['titulo'].' ('.count($fuente['filas']).') ==';

            if ($fuente['filas'] === []) {
                $lineas[] = 'No hay datos.';

                continue;
            }

            foreach ($fuente['filas'] as $i => $fila) {
                $lineas[] = '-- '.($i + 1).' --';
                array_push($lineas, ...$this->renderizar($fila, 0));
            }
        }

        $lineas[] = '';
        $lineas[] = '== Pagos ==';
        $lineas[] = self::SIN_PAGOS;
        $lineas[] = '';
        $lineas[] = self::NO_INCLUYE;

        return implode("\r\n", $lineas)."\r\n";
    }

    /**
     * El mismo contenido, para máquinas.
     */
    public function json(DatosDeUnaPersona $persona, ?CarbonImmutable $ahora = null): string
    {
        $ahora ??= CarbonImmutable::now();

        $datos = [
            'generado_el' => $ahora->toIso8601String(),
            'identificado_por' => ['tipo' => $persona->tipo, 'valor' => $persona->valor],
            'correos_incluidos' => $persona->correos,
            'celulares_incluidos' => $persona->telefonos,
            'conservacion_en_anos' => (int) config('contenido.legal.conservacion_anos'),
            'datos' => array_map(fn (array $fuente): array => $fuente['filas'], $persona->fuentes),
            'pagos' => ['nota' => self::SIN_PAGOS, 'filas' => []],
            'no_incluye' => self::NO_INCLUYE,
        ];

        return json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * Escribe el ZIP en `$ruta` (con los dos archivos adentro) y devuelve la ruta.
     *
     * @throws RuntimeException Si no se puede escribir el ZIP, o si se pidió clave y esta instalación de PHP no puede cifrar.
     */
    public function zip(DatosDeUnaPersona $persona, string $ruta, ?string $clave = null, ?CarbonImmutable $ahora = null): string
    {
        $zip = new ZipArchive;

        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP.');
        }

        $zip->addFromString('datos.txt', $this->texto($persona, $ahora));
        $zip->addFromString('datos.json', $this->json($persona, $ahora));

        if ($clave !== null && $clave !== '') {
            if (! method_exists($zip, 'setEncryptionName')) {
                $zip->close();

                throw new RuntimeException('Esta instalación de PHP no puede proteger un ZIP con clave.');
            }

            foreach (['datos.txt', 'datos.json'] as $nombre) {
                if (! $zip->setEncryptionName($nombre, ZipArchive::EM_AES_256, $clave)) {
                    $zip->close();

                    throw new RuntimeException('No se pudo proteger el ZIP con la clave.');
                }
            }
        }

        if (! $zip->close()) {
            throw new RuntimeException('No se pudo terminar de escribir el archivo ZIP.');
        }

        return $ruta;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return list<string>
     */
    private function renderizar(array $fila, int $nivel): array
    {
        $sangria = str_repeat('  ', $nivel);
        $lineas = [];

        foreach ($fila as $clave => $valor) {
            $etiqueta = self::ETIQUETAS[$clave] ?? ucfirst(str_replace('_', ' ', (string) $clave));

            if (is_array($valor)) {
                if ($valor === []) {
                    $lineas[] = "{$sangria}{$etiqueta}: ninguno";

                    continue;
                }

                $lineas[] = "{$sangria}{$etiqueta}:";

                if (array_is_list($valor)) {
                    foreach ($valor as $i => $elemento) {
                        $lineas[] = $sangria.'  '.($i + 1).'.';
                        array_push($lineas, ...$this->renderizar((array) $elemento, $nivel + 2));
                    }
                } else {
                    array_push($lineas, ...$this->renderizar($valor, $nivel + 1));
                }

                continue;
            }

            $lineas[] = "{$sangria}{$etiqueta}: ".$this->valor($valor);
        }

        return $lineas;
    }

    private function valor(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}T/', $valor) === 1) {
            return $this->fechaLegible($valor);
        }

        return (string) $valor;
    }

    private function fechaLegible(string $iso): string
    {
        return CarbonImmutable::parse($iso)->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }
}
