<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comentarios\Pages;

use App\Enums\EstadoModeracion;
use App\Enums\Via;
use App\Enums\Visibilidad;
use App\Filament\Resources\Comentarios\ComentariosResource;
use App\Models\Comentario;
use App\Models\RecepcionDeComentarios as Interruptor;
use App\Models\ViaDeRecepcion;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
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
 * la pestaña de entrada, los privados y lo ya resuelto están a un clic, y los dos
 * interruptores van arriba, en el encabezado.
 *
 * Son dos y suenan parecido, así que van uno debajo del otro y en el orden en que
 * mandan: **Recepción de comentarios** decide si se admiten comentarios nuevos, y
 * **Vía de recepción** decide por dónde llegan cuando sí se admiten. Con la
 * recepción cerrada la vía no se usa.
 *
 * Lo que esos interruptores existen para aclarar sigue escrito en la pantalla, no
 * solo en la documentación: cerrar la recepción no despublica nada, la cola no se
 * atiende sola, y en la vía de WhatsApp la visibilidad la marca quien captura.
 * Son las tres confusiones fáciles.
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

    /**
     * La Vía de recepción y el celular al que apunta, con el mismo trato: se
     * leen al montar y se escriben por el modelo.
     */
    public string $via = Via::WhatsApp->value;

    public ?string $numeroDeWhatsApp = null;

    public function mount(): void
    {
        parent::mount();

        $this->abierta = Interruptor::estaAbierta();

        $seleccion = ViaDeRecepcion::actual();
        $this->via = $seleccion->via->value;
        $this->numeroDeWhatsApp = $seleccion->numeroDeWhatsApp();
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

            Section::make('Vía de recepción')
                ->description('Con la recepción abierta, esto decide por dónde llegan los comentarios. Es lo otro: el interruptor de arriba dice si se admiten, éste dice por cuál canal. Con la recepción cerrada la vía no se usa.')
                ->schema([
                    Radio::make('via')
                        ->label('Por dónde llegan')
                        ->options([
                            Via::Otp->value => 'En el sitio, validando el celular por SMS',
                            Via::WhatsApp->value => 'Por WhatsApp, a la Mesa Directiva',
                        ])
                        ->descriptions([
                            Via::Otp->value => 'El colono escribe en la página y elige él mismo si su comentario es público o privado. Es la buena, pero necesita que el SMS llegue.',
                            Via::WhatsApp->value => 'La página no pide teléfono ni promete ningún código: muestra un enlace a la conversación con el número de abajo.',
                        ])
                        ->live()
                        ->afterStateUpdated(fn (string $state) => $this->moverVia($state)),

                    TextInput::make('numeroDeWhatsApp')
                        ->label('Número de WhatsApp de la Mesa Directiva')
                        ->tel()
                        ->helperText('Con lada de país, como lo marcas: 52 más los 10 dígitos. Es el número al que abre el enlace de la página.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (?string $state) => $this->cambiarNumero((string) $state)),

                    Callout::make('En WhatsApp, la visibilidad la marcas tú')
                        ->warning()
                        ->description('Cuando el comentario llega por chat, quien lo captura aquí es quien decide si queda público o privado, y esa decisión no se deshace. El mensaje del enlace ya le pide al autor que lo diga por escrito: respeta lo que haya pedido. En el sitio esa elección la hace él, y por eso el modo de SMS es el bueno — éste aguanta mientras aquél no entrega.'),
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

    private function moverVia(string $via): void
    {
        $via = Via::from($via);

        $via === Via::Otp
            ? ViaDeRecepcion::usarOtp()
            : ViaDeRecepcion::usarWhatsApp();

        Notification::make()
            ->title($via === Via::Otp
                ? 'Los comentarios se reciben en el sitio'
                : 'Los comentarios se reciben por WhatsApp')
            ->body($via === Via::Otp
                ? 'La página vuelve a pedir el celular y a mandar el código por SMS. Si el SMS no está llegando, nadie va a poder comentar.'
                : 'La página retira el formulario y muestra el enlace a la conversación. Lo ya publicado sigue ahí.')
            ->success()
            ->send();
    }

    /**
     * El número se guarda en dígitos y se rechaza si no puede serlo: un enlace de
     * `wa.me` mal formado no falla aquí, falla en el celular de un colono que ya
     * se fue a otra pantalla.
     */
    private function cambiarNumero(string $numero): void
    {
        $digitos = (string) preg_replace('/\D/', '', $numero);

        if (strlen($digitos) < 10 || strlen($digitos) > 15) {
            Notification::make()
                ->title('Ese número no se guardó')
                ->body('Escribe el celular con lada de país y sin signos: 52 más los 10 dígitos. El enlace de la página sigue apuntando al número anterior.')
                ->danger()
                ->send();

            $this->numeroDeWhatsApp = ViaDeRecepcion::actual()->numeroDeWhatsApp();

            return;
        }

        ViaDeRecepcion::cambiarNumeroDeWhatsApp($digitos);

        $this->numeroDeWhatsApp = $digitos;

        Notification::make()
            ->title('Listo')
            ->body('El enlace de la página ya abre la conversación con ese número.')
            ->success()
            ->send();
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
