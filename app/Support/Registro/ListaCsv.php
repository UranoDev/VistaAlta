<?php

declare(strict_types=1);

namespace App\Support\Registro;

use App\Models\LoteRegistrado;
use App\Models\RegistroDePropietario;
use Illuminate\Support\Collection;

/**
 * Los registros de propietarios como un CSV que abre Excel, con un renglón por
 * **lote** (el propietario se repite): es la forma que sirve para cruzar contra
 * un padrón o ordenar por calle.
 *
 * ## Por qué cada celda pasa por `celda()`
 *
 * Lo que está en la base lo escribió cualquiera que abrió el formulario, y Excel
 * ejecuta como fórmula toda celda que empieza con `=`, `+`, `-` o `@`. Un nombre
 * como `=HYPERLINK(...)` no debe llegar a la hoja de quien descarga la lista.
 */
final class ListaCsv
{
    public const ENCABEZADO = [
        'Registrado', 'Propietario', 'Teléfono', 'Correo',
        'Calle', 'Núm. oficial', 'Manzana', 'Lote', 'Situación',
        'Otros contactos', 'Contacto de emergencia', 'Teléfono de emergencia', 'Residentes', 'Confirmado',
    ];

    /**
     * @param  Collection<int, RegistroDePropietario>  $registros  Con `lotes` y `contactos` cargados.
     * @return resource Un flujo en memoria, ya en su posición inicial.
     */
    public static function flujo(Collection $registros)
    {
        $flujo = fopen('php://temp', 'r+');

        // La marca de orden de bytes: sin ella, Excel abre el UTF-8 como si fuera
        // ANSI y las ñ y los acentos salen rotos.
        fwrite($flujo, "\xEF\xBB\xBF");
        fputcsv($flujo, self::ENCABEZADO);

        foreach ($registros as $registro) {
            $otros = $registro->contactos
                ->map(fn ($c): string => trim($c->nombre.' '.implode(' / ', array_filter([$c->telefono, $c->correo]))))
                ->implode('; ');

            /** @var LoteRegistrado $lote */
            foreach ($registro->lotes as $lote) {
                fputcsv($flujo, array_map(self::celda(...), [
                    $registro->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                    $registro->nombre,
                    $registro->telefono,
                    $registro->correo,
                    $lote->calle->value,
                    $lote->numero_oficial,
                    $lote->manzana,
                    $lote->lote,
                    $lote->situacion->etiqueta(),
                    $otros,
                    $registro->emergencia_nombre,
                    $registro->emergencia_telefono,
                    $registro->residentes,
                    $registro->confirmado_en?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Pendiente',
                ]));
            }
        }

        rewind($flujo);

        return $flujo;
    }

    /**
     * Neutraliza una celda que Excel leería como fórmula, anteponiéndole una
     * comilla simple, que Excel no muestra.
     */
    public static function celda(mixed $valor): string
    {
        $texto = (string) ($valor ?? '');

        if ($texto !== '' && str_contains("=+-@\t\r", $texto[0])) {
            return "'".$texto;
        }

        return $texto;
    }
}
