<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Pendiente;
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
 * Es idempotente. Las Actividades se reconocen por su fecha y su texto y los
 * Pendientes por su título, así que volver a correrlo no duplica ninguno; el
 * Reporte financiero se reconoce por su mes, así que volver a sembrarlo corrige
 * ese mes y nunca deja dos de junio. Lo que esté vacío en el archivo no se toca
 * — sembrar un contenido no borra los otros.
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
        $this->sembrarPendientes($contenido['pendientes'] ?? []);
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
     * Cada Pendiente se identifica por su título: es lo que lo hace ser ese y no
     * otro. Su posición sale del orden del archivo, que es como se lee la lista
     * —el primero es del que cuelgan los demás— y solo se fija al crearlo: si la
     * Mesa Directiva ya reacomodó la lista desde el panel, volver a sembrar no
     * le deshace el arrastre.
     *
     * @param  array<int, array<string, mixed>>  $pendientes
     */
    private function sembrarPendientes(array $pendientes): void
    {
        $sembrados = 0;
        $orden = 0;

        foreach ($pendientes as $pendiente) {
            $titulo = trim((string) ($pendiente['titulo'] ?? ''));
            $detalle = trim((string) ($pendiente['detalle'] ?? ''));

            // Un pendiente a medias no se publica a medias, igual que una
            // Actividad: el título sin el detalle deja un renglón que no
            // explica por qué sigue pendiente.
            if ($titulo === '' || $detalle === '') {
                $this->aviso('Se saltó un Pendiente sin título o sin detalle.');

                continue;
            }

            Pendiente::query()->firstOrCreate(
                ['titulo' => $titulo],
                ['detalle' => $detalle, 'orden' => $orden],
            );

            $orden++;
            $sembrados++;
        }

        $this->aviso($sembrados > 0
            ? "Pendientes sembrados: {$sembrados}."
            : 'Sin Pendientes en el archivo: la página lo dice en vez de inventarlos.');
    }

    /**
     * Un Reporte se identifica por el mes que cubre: es lo que lo hace ser el de
     * junio y no el de julio. Sembrar el mismo mes dos veces lo corrige;
     * sembrar otro mes lo agrega al histórico sin tocar los que ya estaban.
     *
     * @param  array<string, mixed>  $reporte
     */
    private function sembrarReporteFinanciero(array $reporte): void
    {
        $cifras = $this->cifrasValidas($reporte['cifras'] ?? []);
        $aclaracion = $this->textoONulo($reporte['aclaracion'] ?? null);
        $hojaUrl = $this->textoONulo($reporte['hoja_url'] ?? null);

        if ($cifras === [] && $hojaUrl === null) {
            $this->aviso('Sin Reporte financiero en el archivo: la página lo dice en vez de mostrar un comprobante en blanco.');

            return;
        }

        // Sin mes no hay dónde publicarlo: de él salen la dirección del reporte
        // y su lugar en el histórico. Se salta y se avisa, igual que una
        // Actividad sin fecha.
        $mes = $this->mes($reporte['mes'] ?? null);

        if ($mes === null) {
            $this->aviso('Se saltó el Reporte financiero: le falta el mes que cubre, o no se entiende. Va como «AAAA-MM».');

            return;
        }

        ReporteFinanciero::query()->updateOrCreate(
            ['mes' => $mes->toDateString()],
            [
                'cifras' => $cifras,
                'aclaracion' => $aclaracion,
                'hoja_url' => $hojaUrl,
            ],
        );

        $this->aviso('Reporte financiero de '.ReporteFinanciero::nombreDelMes($mes).' sembrado: '.count($cifras).' cifra(s)'.($hojaUrl ? ' y la hoja de cálculo.' : ', sin hoja de cálculo.'));
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

    /**
     * El mes del Reporte, escrito como «AAAA-MM» y normalizado al día 1. Se
     * valida el número de mes a mano porque `2026-13` no es un error de dedo
     * que convenga tolerar: Carbon lo desbordaría a enero de 2027 y el reporte
     * se publicaría en un mes que nadie escribió.
     */
    private function mes(mixed $valor): ?CarbonImmutable
    {
        $texto = trim((string) ($valor ?? ''));

        if (preg_match('/^(\d{4})-(\d{2})$/', $texto, $partes) !== 1) {
            return null;
        }

        $numero = (int) $partes[2];

        if ($numero < 1 || $numero > 12) {
            return null;
        }

        return CarbonImmutable::create((int) $partes[1], $numero, 1)->startOfDay();
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
