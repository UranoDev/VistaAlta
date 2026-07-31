<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pendientes\Pages;

use App\Filament\Resources\Pendientes\PendientesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPendiente extends EditRecord
{
    protected static string $resource = PendientesResource::class;

    public function getHeading(): string
    {
        return 'Editar pendiente';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->modalHeading('Borrar este pendiente')
                ->modalDescription('Desaparece de la página pública sin pasar a la Bitácora. Si ya se hizo, usa «Ya se hizo».'),
        ];
    }
}
