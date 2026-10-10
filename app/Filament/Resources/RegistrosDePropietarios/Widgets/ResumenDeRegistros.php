<?php

declare(strict_types=1);

namespace App\Filament\Resources\RegistrosDePropietarios\Widgets;

use App\Enums\SituacionDelLote;
use App\Models\LoteRegistrado;
use App\Models\RegistroDePropietario;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Las cifras de arriba de la lista: cuánta gente y cuántos lotes van, y cómo
 * están. Se calculan al abrir la pantalla; no hay nada que mantener.
 *
 * Son lo que cada persona declaró. Un lote registrado dos veces cuenta dos
 * veces: eso se ve en la lista, buscando por calle y número.
 */
class ResumenDeRegistros extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $porSituacion = LoteRegistrado::query()
            ->selectRaw('situacion, count(*) as total')
            ->groupBy('situacion')
            ->pluck('total', 'situacion');

        $enConstruccion = (int) ($porSituacion[SituacionDelLote::EnConstruccion->value] ?? 0);

        return [
            Stat::make('Propietarios registrados', RegistroDePropietario::query()->count())
                ->description(RegistroDePropietario::query()->verificados()->count().' con todo verificado'),
            Stat::make('Por validar', RegistroDePropietario::query()->porValidar()->count())
                ->description(RegistroDePropietario::query()->validados()->count().' ya validados por la Administración'),
            Stat::make('Lotes registrados', LoteRegistrado::query()->count()),
            Stat::make('Con casa terminada', (int) ($porSituacion[SituacionDelLote::CasaTerminada->value] ?? 0))
                ->description($enConstruccion === 1 ? '1 más en construcción' : "{$enConstruccion} más en construcción"),
            Stat::make('Terrenos', (int) ($porSituacion[SituacionDelLote::Terreno->value] ?? 0)),
        ];
    }
}
