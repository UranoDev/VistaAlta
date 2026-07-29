<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comentarios\Pages;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Filament\Resources\Comentarios\ComentariosResource;
use App\Models\Comentario;
use App\Models\RecepcionDeComentarios as Interruptor;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * El único lugar del panel donde se ven los Comentarios: la Cola de moderación es
 * la pestaña de entrada, los privados y lo ya resuelto están a un clic, y el
 * interruptor de Recepción de comentarios va arriba, en el encabezado.
 *
 * Las dos cosas que ese interruptor existe para aclarar siguen escritas en la
 * pantalla, no solo en la documentación: cerrarlo no despublica nada, y la cola no
 * se atiende sola. Son las dos confusiones fáciles.
 */
class ListComentarios extends ListRecords
{
    protected static string $resource = ComentariosResource::class;

    /**
     * La pestaña de entrada: a lo que se entra a esta pantalla es a atender la
     * cola.
     */
    private const COLA = 'en-cola';

    /**
     * El estado del interruptor mientras dura la petición de Livewire. Se lee de
     * la base al montar y se escribe con abrir()/cerrar(), nunca al revés: la
     * fuente de verdad sigue siendo el modelo.
     */
    public bool $abierta = true;

    public function mount(): void
    {
        parent::mount();

        $this->abierta = Interruptor::estaAbierta();
    }

    public function getHeading(): string
    {
        return 'Comentarios';
    }

    public function getSubheading(): string
    {
        return 'Todo lo que llegó por la página de la Propuesta, público y privado. Ningún comentario público existe de cara a la Asamblea hasta que la Mesa Directiva lo publica desde aquí.';
    }

    public function table(Table $table): Table
    {
        // La cola se atiende en el orden en que llegaron; el resto se lee de lo
        // más reciente hacia atrás. Va como closure para que se resuelva al
        // renderizar, cuando `activeTab` ya está puesto.
        return parent::table($table)
            ->defaultSort('created_at', fn (): string => $this->activeTab === self::COLA ? 'asc' : 'desc');
    }

    /**
     * El interruptor va en el encabezado, arriba de las pestañas — no como una
     * fila más de la tabla.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Recepción de comentarios')
                ->description('Decide si la página de la Propuesta admite Comentarios nuevos. Es distinto de qué se publica: eso se decide aquí abajo, en la pestaña «En cola».')
                ->schema([
                    Toggle::make('abierta')
                        ->label('Admitir comentarios nuevos')
                        ->helperText('Abierta: la página muestra el formulario. Cerrada: lo retira y rechaza cualquier envío.')
                        ->onColor('success')
                        ->offColor('danger')
                        ->live()
                        ->afterStateUpdated(fn (bool $state) => $this->mover($state)),

                    Callout::make('Cerrarla no despublica nada')
                        ->info()
                        ->description('Los Comentarios que ya publicaste siguen visibles en la página de la Propuesta, con la recepción abierta o cerrada. Este interruptor decide si se puede escribir; qué se publica lo decide la Cola de moderación.'),

                    Callout::make('La cola no se atiende sola')
                        ->warning()
                        ->description('La recepción nace abierta y nadie la cierra por su cuenta: los Comentarios públicos van a seguir llegando indefinidamente. Si nadie revisa la Cola de moderación, se apilan sin publicar y los colonos concluyen que se les está ignorando. Alguien de la Mesa Directiva tiene que quedar a cargo de revisarla.'),
                ]),

            $this->getTabsContentComponent(),

            EmbeddedTable::make(),
        ]);
    }

    /**
     * La cola es la pestaña por omisión: es a lo que se entra a hacer. «Privados»
     * es una lista y nada más — ahí no hay nada que resolver. «Publicados» y
     * «Descartados» existen porque publicar es irreversible de cara a la Asamblea
     * si no hay dónde encontrar lo ya publicado, y porque un descarte por error
     * tiene que poder deshacerse. «Todos» es para recorrer un teléfono completo.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            self::COLA => Tab::make('En cola')
                ->badge(Comentario::enCola()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('visibilidad', Visibilidad::Publico)
                    ->where('estado', EstadoModeracion::EnCola)),

            'privados' => Tab::make('Privados')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('visibilidad', Visibilidad::Privado)),

            'publicados' => Tab::make('Publicados')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('visibilidad', Visibilidad::Publico)
                    ->where('estado', EstadoModeracion::Publicado)),

            'descartados' => Tab::make('Descartados')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('visibilidad', Visibilidad::Publico)
                    ->where('estado', EstadoModeracion::Descartado)),

            'todos' => Tab::make('Todos'),
        ];
    }

    private function mover(bool $abierta): void
    {
        $abierta ? Interruptor::abrir() : Interruptor::cerrar();

        Notification::make()
            ->title($abierta
                ? 'La recepción quedó abierta'
                : 'La recepción quedó cerrada')
            ->body($abierta
                ? 'La página de la Propuesta vuelve a mostrar el formulario.'
                : 'La página retira el formulario. Lo ya publicado sigue ahí.')
            ->success()
            ->send();
    }
}
