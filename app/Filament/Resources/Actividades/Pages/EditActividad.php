<?php

declare(strict_types=1);

namespace App\Filament\Resources\Actividades\Pages;

use App\Filament\Resources\Actividades\ActividadesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActividad extends EditRecord
{
    protected static string $resource = ActividadesResource::class;

    public function getHeading(): string
    {
        return 'Editar actividad';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Borrar esta actividad')
                ->modalDescription('Desaparece de la página pública. No hay forma de recuperarla.'),
        ];
    }
}
