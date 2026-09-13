<?php

declare(strict_types=1);

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostsResource;
use App\Models\IntroDeConvivencia;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * La pantalla de Convivencia: la introducción del índice arriba, en el
 * encabezado, y los posts abajo en la tabla.
 *
 * La introducción va aquí y no como un post más porque no es una publicación:
 * es el párrafo que explica de qué va la sección, y aparece una sola vez, arriba
 * de todo. Colgarla del encabezado sigue el mismo reparto que la pantalla de
 * Comentarios, donde los interruptores de la Recepción viven arriba de la cola.
 */
class ListPosts extends ListRecords
{
    protected static string $resource = PostsResource::class;

    /**
     * El texto mientras dura la petición de Livewire. Se lee de la base al
     * montar y se escribe con `cambiar()`, nunca al revés: la fuente de verdad
     * sigue siendo el modelo.
     */
    public ?string $intro = null;

    public function mount(): void
    {
        parent::mount();

        $this->intro = IntroDeConvivencia::texto();
    }

    public function getHeading(): string
    {
        return 'Convivencia';
    }

    public function getSubheading(): string
    {
        return 'Lo que la Mesa Directiva publica sobre cómo se vive el fraccionamiento. Cada post se lee en su propia dirección y se puede compartir por separado; no hay borradores.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Introducción del índice')
                ->description('El párrafo que encabeza /convivencia y dice para qué es la sección. Es lo primero que se lee, antes de la lista de publicaciones.')
                ->collapsed(fn (): bool => filled($this->intro))
                ->schema([
                    Textarea::make('intro')
                        ->hiddenLabel()
                        ->helperText('Se puede dejar vacío: el índice arranca directo con las publicaciones. Para enlazar a otra página del sitio, escribe [texto](/ruta).')
                        ->rows(4)
                        ->maxLength(1000)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state) => $this->cambiarIntro($state)),
                ]),

            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo post'),
        ];
    }

    /**
     * Vaciarla es una acción legítima y no un descuido —el índice se dibuja sin
     * introducción—, así que se confirma igual que capturarla. Un guardado que
     * no avisa deja a quien acaba de borrar el párrafo sin saber si se guardó.
     */
    private function cambiarIntro(?string $texto): void
    {
        IntroDeConvivencia::cambiar($texto);

        $this->intro = IntroDeConvivencia::texto();

        Notification::make()
            ->title($this->intro === null
                ? 'El índice queda sin introducción'
                : 'Se guardó la introducción')
            ->body($this->intro === null
                ? 'La página de Convivencia arranca directo con las publicaciones.'
                : 'Ya se lee arriba de las publicaciones en la página de Convivencia.')
            ->success()
            ->send();
    }
}
