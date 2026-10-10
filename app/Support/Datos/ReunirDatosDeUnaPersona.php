<?php

declare(strict_types=1);

namespace App\Support\Datos;

use App\Models\Comentario;
use App\Models\ContactoDelRegistro;
use App\Models\LoteRegistrado;
use App\Models\Otp;
use App\Models\RegistroDePropietario;
use App\Models\SolicitudDeInternet;
use App\Support\Telefono;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Junta todo lo que el sitio tiene de una persona, a partir de su correo o de su
 * celular. Es lo que hace falta para atender una solicitud de acceso a los datos
 * (derechos ARCO, sección 5 del Aviso de Privacidad).
 *
 * ## A quién se le suma qué
 *
 * El identificador que se da es el punto de partida, y a la persona se le suma
 * **únicamente lo que quedó verificado junto con él**: si se identifica por
 * correo, su celular entra solo si ese celular se verificó en un registro donde
 * también se verificó ese correo, y al revés. Sin esa regla bastaría registrarse
 * con el celular de otra persona y el correo propio para que, al pedir «mis
 * datos», le entregaran los comentarios y la lista de internet del celular
 * ajeno.
 *
 * Lo que sí se incluye, sin exigir verificación, es todo registro donde aparezca
 * el identificador mismo: son datos que alguien declaró sobre esa persona y tiene
 * derecho a saberlo.
 *
 * ## Qué no sale de aquí
 *
 * Nada de lo que sea de otra persona: los lotes y los datos de quien registró a
 * esta persona como contacto no se entregan, solo lo que se sabe de ella. Y el
 * nombre de quien en la Administración validó un registro tampoco: es dato de esa
 * persona, no de quien pide.
 *
 * ## Los pagos
 *
 * Todavía no hay pagos en este sitio (viven en la rama del control de cuotas), y
 * esa fuente sale vacía y con su aviso. Cuando existan, se agrega aquí.
 */
final class ReunirDatosDeUnaPersona
{
    /**
     * @throws InvalidArgumentException Si el identificador no es un correo ni un celular de diez dígitos.
     */
    public function para(string $identificador): DatosDeUnaPersona
    {
        [$tipo, $valor] = $this->identificar($identificador);

        $registrosDelIdentificador = RegistroDePropietario::query()
            ->where($tipo === 'correo' ? 'correo' : 'telefono', $valor)
            ->get();

        $correos = $tipo === 'correo' ? [$valor] : [];
        $telefonos = $tipo === 'celular' ? [$valor] : [];
        $correosVerificadosDelCelular = [];

        foreach ($registrosDelIdentificador as $registro) {
            // Solo si los dos medios se verificaron en el mismo registro: ahí
            // la persona demostró que controla ambos.
            if (! $registro->correoVerificado() || ! $registro->telefonoVerificado()) {
                continue;
            }

            if ($tipo === 'correo') {
                $telefonos[] = $registro->telefono;
            } else {
                $correos[] = $registro->correo;
                $correosVerificadosDelCelular[] = $registro->correo;
            }
        }

        $correos = array_values(array_unique(array_filter($correos)));
        $telefonos = array_values(array_unique(array_filter($telefonos)));

        return new DatosDeUnaPersona(
            tipo: $tipo,
            valor: $valor,
            correos: $correos,
            telefonos: $telefonos,
            correosVerificadosDelCelular: array_values(array_unique(array_filter($correosVerificadosDelCelular))),
            fuentes: [
                'registros_de_propietario' => [
                    'titulo' => 'Registro de propietario',
                    'filas' => $this->registros($correos, $telefonos),
                ],
                'aparece_como_contacto' => [
                    'titulo' => 'Aparece como contacto de otra persona',
                    'filas' => $this->comoContacto($correos, $telefonos),
                ],
                'lista_de_espera_de_internet' => [
                    'titulo' => 'Lista de espera de internet',
                    'filas' => $this->solicitudesDeInternet($telefonos),
                ],
                'comentarios' => [
                    'titulo' => 'Comentarios',
                    'filas' => $this->comentarios($telefonos),
                ],
                'codigos_de_verificacion' => [
                    'titulo' => 'Códigos de verificación pedidos',
                    'filas' => $this->codigos($telefonos),
                ],
            ],
        );
    }

    /**
     * @return array{0: 'correo'|'celular', 1: string}
     */
    private function identificar(string $identificador): array
    {
        $identificador = trim($identificador);

        if (str_contains($identificador, '@')) {
            if (filter_var($identificador, FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException('El correo no es válido.');
            }

            return ['correo', mb_strtolower($identificador)];
        }

        $celular = Telefono::aDiezDigitos($identificador);

        if (preg_match('/^\d{10}$/', $celular) !== 1) {
            throw new InvalidArgumentException('Escribe un correo, o un celular a 10 dígitos.');
        }

        return ['celular', $celular];
    }

    /**
     * @param  list<string>  $correos
     * @param  list<string>  $telefonos
     * @return list<array<string, mixed>>
     */
    private function registros(array $correos, array $telefonos): array
    {
        return RegistroDePropietario::query()
            ->with(['lotes', 'contactos'])
            ->where(fn ($q) => $q->whereIn('correo', $correos)->orWhereIn('telefono', $telefonos))
            ->orderBy('id')
            ->get()
            ->map(fn (RegistroDePropietario $r): array => [
                'nombre' => $r->nombre,
                'celular' => $r->telefono,
                'correo' => $r->correo,
                'lotes' => $r->lotes->map(fn (LoteRegistrado $l): array => [
                    'calle' => $l->calle->value,
                    'numero_oficial' => $l->numero_oficial ?: null,
                    'manzana' => $l->manzana ?: null,
                    'lote' => $l->lote ?: null,
                    'tipo_de_propiedad' => $l->situacion->etiqueta(),
                ])->all(),
                'contactos_adicionales' => $r->contactos->map(fn (ContactoDelRegistro $c): array => [
                    'nombre' => $c->nombre,
                    'celular' => $c->telefono,
                    'correo' => $c->correo,
                ])->all(),
                'contacto_de_emergencia' => $r->emergencia_nombre === null && $r->emergencia_telefono === null ? null : [
                    'nombre' => $r->emergencia_nombre,
                    'celular' => $r->emergencia_telefono,
                ],
                'acepto_el_aviso_de_privacidad_el' => $this->fecha($r->aceptado_en),
                'version_del_aviso' => $r->aviso_version,
                'correo_verificado_el' => $this->fecha($r->correo_verificado_en),
                'correo_verificado_con' => $r->correo_verificado_por?->value,
                'celular_verificado_el' => $this->fecha($r->telefono_verificado_en),
                'validado_por_la_administracion_el' => $this->fecha($r->validado_en),
                'nota_de_la_validacion' => $r->validacion_nota,
                'registrado_el' => $this->fecha($r->created_at),
                'actualizado_el' => $this->fecha($r->updated_at),
            ])
            ->all();
    }

    /**
     * Donde otra persona la dio como contacto o como contacto de emergencia.
     * Solo se entrega lo que se sabe de quien pide, nunca quién la registró.
     *
     * @param  list<string>  $correos
     * @param  list<string>  $telefonos
     * @return list<array<string, mixed>>
     */
    private function comoContacto(array $correos, array $telefonos): array
    {
        $contactos = ContactoDelRegistro::query()
            ->where(fn ($q) => $q->whereIn('correo', $correos)->orWhereIn('telefono', $telefonos))
            ->orderBy('id')
            ->get()
            ->map(fn (ContactoDelRegistro $c): array => [
                'como' => 'Contacto adicional',
                'nombre' => $c->nombre,
                'celular' => $c->telefono,
                'correo' => $c->correo,
                'registrado_el' => $this->fecha($c->created_at),
            ]);

        $emergencias = RegistroDePropietario::query()
            ->whereIn('emergencia_telefono', $telefonos)
            ->orderBy('id')
            ->get()
            ->map(fn (RegistroDePropietario $r): array => [
                'como' => 'Contacto de emergencia',
                'nombre' => $r->emergencia_nombre,
                'celular' => $r->emergencia_telefono,
                'correo' => null,
                'registrado_el' => $this->fecha($r->created_at),
            ]);

        return $contactos->concat($emergencias)->values()->all();
    }

    /**
     * @param  list<string>  $telefonos
     * @return list<array<string, mixed>>
     */
    private function solicitudesDeInternet(array $telefonos): array
    {
        return SolicitudDeInternet::query()
            ->whereIn('celular', $telefonos)
            ->orderBy('numero')
            ->get()
            ->map(fn (SolicitudDeInternet $s): array => [
                'folio' => $s->folio(),
                'celular' => $s->celular,
                'domicilio' => $s->domicilio(),
                'anotada_el' => $this->fecha($s->created_at),
                'actualizada_el' => $this->fecha($s->updated_at),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $telefonos
     * @return list<array<string, mixed>>
     */
    private function comentarios(array $telefonos): array
    {
        return Comentario::query()
            ->whereIn('telefono', $telefonos)
            ->orderBy('id')
            ->get()
            ->map(fn (Comentario $c): array => [
                'nombre_con_el_que_firmo' => $c->nombre,
                'celular' => $c->telefono,
                'comentario' => $c->comentario,
                'visibilidad' => $c->visibilidad->value,
                'estado' => $c->estado?->value,
                'escrito_el' => $this->fecha($c->created_at),
                'actualizado_el' => $this->fecha($c->updated_at),
            ])
            ->all();
    }

    /**
     * Cuándo se pidieron códigos de verificación para ese celular. El código ni su
     * huella salen: no son datos suyos, son del mecanismo.
     *
     * @param  list<string>  $telefonos
     * @return list<array<string, mixed>>
     */
    private function codigos(array $telefonos): array
    {
        return Otp::query()
            ->whereIn('telefono', $telefonos)
            ->orderBy('id')
            ->get()
            ->map(fn (Otp $o): array => [
                'celular' => $o->telefono,
                'para' => $o->proposito,
                'pedido_el' => $this->fecha($o->created_at),
                'verificado_el' => $this->fecha($o->verificado_en),
            ])
            ->all();
    }

    private function fecha(?CarbonInterface $fecha): ?string
    {
        return $fecha?->timezone(config('app.timezone'))->format(DATE_ATOM);
    }
}
