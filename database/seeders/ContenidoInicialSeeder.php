<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\ReporteFinanciero;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Siembra el contenido con el que el sitio sale al aire: las Actividades del
 * Periodo y el Reporte financiero que las respalda.
 *
 * El material lo manda la Mesa Directiva y se pega en
 * `database/seeders/contenido/contenido-inicial.php`; este seeder solo lo
 * empuja a la base. La separación importa: quien pega el material no debería
 * tener que leer PHP más allá de una lista, y quien toca este archivo no
 * debería estar editando cifras.
 *
 * Es idempotente. Las Actividades se reconocen por su fecha y su texto, así que
 * volver a correrlo no duplica ninguna; el Reporte financiero es un solo
 * renglón y se sobreescribe. Lo que esté vacío en el archivo no se toca —
 * sembrar un contenido no borra los otros.
 */
class ContenidoInicialSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * El contenido a sembrar. Cuando es `null` se lee del archivo; se puede
     * asignar para sembrar otro material sin escribirlo en disco, que es como
     * lo ejercitan las pruebas.
     *
     * @var array<string, mixed>|null
     */
    public ?array $contenido = null;

    public function run(): void
    {
        $contenido = $this->contenido ?? require database_path('seeders/contenido/contenido-inicial.php');

        $this->sembrarActividades($contenido['actividades'] ?? []);
        $this->sembrarReporteFinanciero($contenido['reporte_financiero'] ?? []);
    }

    /**
     * Cada Actividad se identifica por su fecha y su texto: es lo que la hace
     * ser esa y no otra. Si el archivo trae dos veces lo mismo, en la base
     * queda una.
     *
     * @param  array<int, array<string, mixed>>  $actividades
     */
    private function sembrarActividades(array $actividades): void
    {
        $sembradas = 0;

        foreach ($actividades as $actividad) {
            $fecha = $this->fecha($actividad['fecha'] ?? null);
            $descripcion = trim((string) ($actividad['descripcion'] ?? ''));

            // Una Actividad a medias no se publica a medias: se salta y se
            // avisa, para que quien pegó el material lo note antes que la
            // Asamblea.
            if ($fecha === null || $descripcion === '') {
                $this->aviso('Se saltó una Actividad sin descripción o con una fecha que no se entiende.');

                continue;
            }

            // La fecha va normalizada al inicio del día porque así queda
            // guardada: comparar el texto crudo contra la columna nunca
            // encontraría la Actividad ya sembrada, y la duplicaría.
            Actividad::query()->firstOrCreate([
                'fecha' => $fecha,
                'descripcion' => $descripcion,
            ]);

            $sembradas++;
        }

        $this->aviso($sembradas > 0
            ? "Actividades sembradas: {$sembradas}."
            : 'Sin Actividades en el archivo: la página lo dice en vez de inventarlas.');
    }

    /**
     * El Reporte es una tabla de un solo renglón, así que sembrarlo es llenar
     * ese renglón —exista o no— y nunca agregar un segundo.
     *
     * @param  array<string, mixed>  $reporte
     */
    private function sembrarReporteFinanciero(array $reporte): void
    {
        $cifras = $this->cifrasValidas($reporte['cifras'] ?? []);
        $periodo = $this->textoONulo($reporte['periodo'] ?? null);
        $hojaUrl = $this->textoONulo($reporte['hoja_url'] ?? null);

        if ($cifras === [] && $periodo === null && $hojaUrl === null) {
            $this->aviso('Sin Reporte financiero en el archivo: la página lo dice en vez de mostrar un comprobante en blanco.');

            return;
        }

        $actual = ReporteFinanciero::actual();
        $actual->fill([
            'periodo' => $periodo,
            'cifras' => $cifras,
            'hoja_url' => $hojaUrl,
        ]);
        $actual->save();

        $this->aviso('Reporte financiero sembrado: '.count($cifras).' cifra(s)'.($hojaUrl ? ' y la hoja de cálculo.' : ', sin hoja de cálculo.'));
    }

    /**
     * Deja pasar solo los renglones que la página sabe mostrar. Uno incompleto
     * se descarta aquí en vez de quedar guardado esperando a romper la vista.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cifrasValidas(mixed $cifras): array
    {
        if (! is_array($cifras)) {
            return [];
        }

        $validas = [];

        foreach ($cifras as $cifra) {
            if (! is_array($cifra) || trim((string) ($cifra['concepto'] ?? '')) === '' || ! is_numeric($cifra['monto'] ?? null)) {
                $this->aviso('Se saltó una cifra sin concepto o sin monto.');

                continue;
            }

            $validas[] = [
                'concepto' => trim((string) $cifra['concepto']),
                'monto' => (float) $cifra['monto'],
                'destacada' => (bool) ($cifra['destacada'] ?? false),
            ];
        }

        return $validas;
    }

    /**
     * La fecha del material, al inicio del día. Una fecha mal escrita se
     * descarta en lugar de tumbar la siembra completa: lo que se pega aquí lo
     * escribió alguien a mano y contra reloj.
     */
    private function fecha(mixed $valor): ?CarbonImmutable
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '') {
            return null;
        }

        return rescue(fn (): CarbonImmutable => CarbonImmutable::parse($texto)->startOfDay(), null, report: false);
    }

    private function textoONulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }

    /**
     * El seeder también corre desde las pruebas, donde no hay consola de la
     * cual colgarse.
     */
    private function aviso(string $mensaje): void
    {
        $this->command?->getOutput()->writeln("  <fg=gray>{$mensaje}</>");
    }
}
