<?php

declare(strict_types=1);

namespace App\Filament\Resources\Actividades\Pages;

use App\Filament\Resources\Actividades\ActividadesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActividades extends ListRecords
{
    protected static string $resource = ActividadesResource::class;

    public function getHeading(): string
    {
        return 'Actividades';
    }

    public function getSubheading(): string
    {
        return 'Lo que la Mesa Directiva llevó a cabo durante el Periodo. Todo lo que está aquí se lee en el sitio: no hay borradores.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva actividad'),
        ];
    }
}
