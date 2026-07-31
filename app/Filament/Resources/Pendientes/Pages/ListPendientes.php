<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendientesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPendientes extends ListRecords
{
    protected static string $resource = PendientesResource::class;

    public function getHeading(): string
    {
        return 'Lo que sigue';
    }

    public function getSubheading(): string
    {
        return 'Lo que todavía falta, en el orden en que se publica. Arrástralos para reacomodarlos; cuando uno se cumpla, «Ya se hizo» lo pasa a la Bitácora.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo pendiente'),
        ];
    }
}
