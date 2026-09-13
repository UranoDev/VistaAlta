<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostsResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPost extends EditRecord
{
    protected static string $resource = PostsResource::class;

    public function getHeading(): string
    {
        return 'Editar post';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ver')
                ->label('Ver publicado')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                // De la dirección guardada y no de la que esté en el formulario:
                // mientras el slug editado no se guarde, esa página no existe.
                ->url(fn (Post $record): string => $record->urlPublica())
                ->openUrlInNewTab(),

            DeleteAction::make()
                ->modalHeading('Borrar este post')
                ->modalDescription('Desaparece del índice y su dirección queda en 404. No hay forma de recuperarlo.'),
        ];
    }
}
