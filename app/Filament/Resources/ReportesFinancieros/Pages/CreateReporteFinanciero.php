<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportesFinancieros\Pages;

use App\Filament\Resources\ReportesFinancieros\ReportesFinancierosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReporteFinanciero extends CreateRecord
{
    protected static string $resource = ReportesFinancierosResource::class;

    public function getHeading(): string
    {
        return 'Capturar un mes';
    }

    public function getSubheading(): string
    {
        return 'Si el mes que captures es el más reciente, pasa a ser lo que publica /reporte-financiero y el anterior se archiva solo.';
    }
}
