<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportesFinancieros\Pages;

use App\Filament\Resources\ReportesFinancieros\ReportesFinancierosResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListReportesFinancieros extends ListRecords
{
    protected static string $resource = ReportesFinancierosResource::class;

    public function getHeading(): string
    {
        return 'Reporte financiero';
    }

    public function getSubheading(): string
    {
        return 'Un reporte por mes. El más reciente es el que se publica en /reporte-financiero; los anteriores quedan consultables con su propia dirección y no se retiran.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verPaginaPublica')
                ->label('Ver lo publicado')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->tooltip('Abre en una pestaña nueva el reporte vigente, tal como lo lee la Asamblea.')
                ->url(route('reporte-financiero'), shouldOpenInNewTab: true),

            CreateAction::make()->label('Capturar un mes'),
        ];
    }
}
