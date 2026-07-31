<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendientesResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePendiente extends CreateRecord
{
    protected static string $resource = PendientesResource::class;

    public function getHeading(): string
    {
        return 'Nuevo pendiente';
    }
}
