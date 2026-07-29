<?php

declare(strict_types=1);

namespace App\Filament\Resources\Actividades\Pages;

use App\Filament\Resources\Actividades\ActividadesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActividad extends CreateRecord
{
    protected static string $resource = ActividadesResource::class;

    public function getHeading(): string
    {
        return 'Nueva actividad';
    }
}
