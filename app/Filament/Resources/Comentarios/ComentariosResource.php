<?php

declare(strict_types=1);

namespace App\Filament\Resources\Comentarios;

use App\Enums\EstadoModeracion;
use App\Filament\Resources\Comentarios\Pages\ListComentarios;
use App\Models\Comentario;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * La única pantalla de Comentarios del panel: todo lo que llegó —público y
 * privado— en una sola lista, con la Cola de moderación como pestaña de entrada y
 * el interruptor de Recepción de comentarios en el encabezado.
 *
 * **La garantía del Comentario privado ya no vive en la consulta.** Antes eran dos
 * recursos con `getEloquentQuery()` acotado por visibilidad, y un privado no se
 * alcanzaba ni por URL. Con la lista fundida ese filtro no puede existir —la
 * pantalla tiene que mostrar ambos—, así que la garantía es por registro y en tres
 * capas, todas necesarias:
 *
 * 1. Las acciones de publicar y descartar no se ofrecen sobre un privado.
 * 2. Un privado no lleva casilla de selección utilizable, así que no entra a un
 *    lote (`checkIfRecordIsSelectableUsing`).
 * 3. `enLote()` filtra a públicos antes de iterar. Es la capa que sostiene la
 *    garantía cuando alguien se sale de la interfaz: en uso normal no descarta
 *    nada.
 *
 * `ComentarioPrivadoNoSeModera` en el modelo sigue siendo la última red, no la
 * segunda.
 */
class ComentariosResource extends Resource
{
    protected static ?string $model = Comentario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $slug = 'comentarios';

    protected static ?string $navigationLabel = 'Comentarios';

    protected static ?string $modelLabel = 'comentario';

    protected static ?string $pluralModelLabel = 'comentarios';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?int $navigationSort = 10;

    /**
     * Cuántos esperan en la Cola de moderación. Va en la navegación para que no
     * haga falta entrar a saber si hay algo pendiente.
     */
    public static function getNavigationBadge(): ?string
    {
        $enCola = Comentario::enCola()->count();

        return $enCola > 0 ? (string) $enCola : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('nombre')->label('Nombre'),

                // La tabla es para reconocer de un vistazo; el detalle es para
                // copiar el número cuando hay que responder.
                TextEntry::make('telefono')
                    ->label('Teléfono')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->copyMessage('Teléfono copiado'),

                TextEntry::make('created_at')->label('Fecha')->dateTime('d/M/Y H:i'),

                TextEntry::make('comentario')->label('Comentario')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/M/Y H:i')
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->description(fn (Comentario $record): string => $record->telefono)
                    ->searchable(query: self::porNombreOTelefono()),

                TextColumn::make('comentario')
                    ->label('Comentario')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    // Un privado trae `estado = null` a propósito (nace fuera de la
                    // cola). Sin este caso se leería «—», que parece dato faltante.
                    ->formatStateUsing(fn (Comentario $record, ?EstadoModeracion $state): string => match (true) {
                        $record->esPrivado() => 'Privado',
                        $state === EstadoModeracion::EnCola => 'En cola',
                        $state === EstadoModeracion::Publicado => 'Publicado',
                        $state === EstadoModeracion::Descartado => 'Descartado',
                        default => '—',
                    })
                    ->color(fn (Comentario $record, ?EstadoModeracion $state): string => match (true) {
                        $record->esPrivado() => 'gray',
                        $state === EstadoModeracion::Publicado => 'success',
                        $state === EstadoModeracion::Descartado => 'danger',
                        default => 'warning',
                    }),
            ])
            // El término se normaliza completo antes de comparar con el teléfono, y
            // con las palabras separadas cada una iría a su propio grupo: buscar
            // «55 3126 9267» dejaría de encontrar lo mismo que «5531269267».
            ->splitSearchTerms(false)
            // Un privado no se puede seleccionar: sin esto existiría la selección
            // mixta, y habría que explicar por qué se omitieron registros del lote.
            ->checkIfRecordIsSelectableUsing(fn (Comentario $record): bool => $record->esPublico())
            ->recordActions([
                ViewAction::make()->label('Leer completo'),

                Action::make('publicar')
                    ->label('Publicar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publicar este comentario')
                    ->modalDescription('Va a aparecer en la página de la Propuesta, a la vista de toda la Asamblea.')
                    ->modalSubmitActionLabel('Publicar')
                    ->visible(fn (Comentario $record): bool => $record->esPublico()
                        && $record->estado !== EstadoModeracion::Publicado)
                    ->action(function (Comentario $record): void {
                        $record->publicar();

                        self::avisar('El comentario ya aparece en la página de la Propuesta.');
                    }),

                Action::make('descartar')
                    ->label('Descartar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Descartar este comentario')
                    ->modalDescription('Sale de la cola sin publicarse. Se puede volver a publicar desde la pestaña «Descartados».')
                    ->modalSubmitActionLabel('Descartar')
                    ->visible(fn (Comentario $record): bool => $record->esPublico()
                        && $record->estado !== EstadoModeracion::Descartado)
                    ->action(function (Comentario $record): void {
                        $record->descartar();

                        self::avisar('El comentario salió de la cola sin publicarse.');
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('publicar')
                    ->label('Publicar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Publicar los comentarios seleccionados')
                    ->modalDescription('Van a aparecer en la página de la Propuesta, a la vista de toda la Asamblea.')
                    ->modalSubmitActionLabel('Publicar')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => self::enLote($records, 'publicar')),

                BulkAction::make('descartar')
                    ->label('Descartar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Descartar los comentarios seleccionados')
                    ->modalDescription('Salen de la cola sin publicarse.')
                    ->modalSubmitActionLabel('Descartar')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn (Collection $records) => self::enLote($records, 'descartar')),
            ])
            ->emptyStateHeading('No hay comentarios')
            ->emptyStateDescription('Aquí llega todo lo que se escribe en la página de la Propuesta, público y privado.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComentarios::route('/'),
        ];
    }

    /**
     * La Mesa Directiva no escribe Comentarios ni los edita: solo decide si se
     * publican. Borrar tampoco — para eso está descartar, que deja rastro.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Busca por nombre con el texto tal cual y por teléfono con los dígitos.
     *
     * Los teléfonos se guardan en puros dígitos
     * (`PropuestaController::normalizarTelefono()`), así que el término de
     * búsqueda se normaliza igual: el mismo celular escrito de cualquier forma
     * encuentra sus comentarios. Por contención, además, `+52…` se encuentra
     * buscando los 10 dígitos.
     *
     * Los `orWhere` van agrupados a propósito: sin el paréntesis el `or` se
     * escaparía y se llevaría por delante el filtro de la pestaña activa —buscar
     * en «Privados» empezaría a devolver públicos.
     */
    private static function porNombreOTelefono(): Closure
    {
        return function (Builder $query, string $search): Builder {
            $digitos = preg_replace('/\D/', '', $search);

            return $query->where(function (Builder $query) use ($search, $digitos): void {
                $query->where('nombre', 'like', "%{$search}%")
                    // Sin dígitos no se busca por teléfono: un `%%` empataría con
                    // todos los registros.
                    ->when($digitos !== '', fn (Builder $query) => $query->orWhere('telefono', 'like', "%{$digitos}%"));
            });
        };
    }

    /**
     * @param  Collection<int, Comentario>  $comentarios
     * @param  'publicar'|'descartar'  $accion
     */
    private static function enLote(Collection $comentarios, string $accion): void
    {
        // Que la interfaz no ofrezca la casilla no es garantía: una petición armada
        // a mano puede traer el ID de un privado. Sin este filtro la iteración
        // reventaría a medio camino con ComentarioPrivadoNoSeModera, dejando
        // publicada solo una parte del lote.
        $publicos = $comentarios->filter(fn (Comentario $comentario): bool => $comentario->esPublico());

        foreach ($publicos as $comentario) {
            $comentario->{$accion}();
        }

        $movidos = $publicos->count();

        self::avisar($accion === 'publicar'
            ? trans_choice('{0} Ningún comentario se publicó.|{1} Se publicó 1 comentario.|[2,*] Se publicaron :count comentarios.', $movidos, ['count' => $movidos])
            : trans_choice('{0} Ningún comentario se descartó.|{1} Se descartó 1 comentario.|[2,*] Se descartaron :count comentarios.', $movidos, ['count' => $movidos]));
    }

    private static function avisar(string $cuerpo): void
    {
        Notification::make()
            ->title('Listo')
            ->body($cuerpo)
            ->success()
            ->send();
    }
}
