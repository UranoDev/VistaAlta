<?php

declare(strict_types=1);

namespace App\Filament\Resources\SolicitudesDeInternet;

use App\Enums\Calle;
use App\Filament\Resources\SolicitudesDeInternet\Pages\ListSolicitudesDeInternet;
use App\Models\SolicitudDeInternet;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * La lista de espera de internet, en el orden en que se anotaron: el folio más
 * bajo es el primero que se atiende.
 *
 * Es de lectura: las solicitudes las crea el formulario de `/internet`, y
 * corregir una a mano la vuelve lo que dijo quien la corrigió. Lo que sí se puede
 * es borrarla (un duplicado, una captura de broma); su folio no se reutiliza ni
 * mueve los demás.
 */
class SolicitudesDeInternetResource extends Resource
{
    protected static ?string $model = SolicitudDeInternet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

    protected static ?string $slug = 'internet';

    protected static ?string $navigationLabel = 'Internet';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'solicitudes de internet';

    protected static ?int $navigationSort = 65;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->label('Folio')
                    ->formatStateUsing(fn (int $state): string => 'INT-'.str_pad((string) $state, 3, '0', STR_PAD_LEFT))
                    ->weight('bold')
                    ->sortable()
                    // «INT-007», «007» y «7» encuentran el mismo folio.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->when(
                        preg_match('/\d+/', $search, $m) === 1,
                        fn (Builder $q): Builder => $q->orWhere('numero', (int) $m[0]),
                    )),

                TextColumn::make('calle')
                    ->label('Calle')
                    ->searchable(),

                TextColumn::make('numero_oficial')
                    ->label('Núm. oficial')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('manzana')
                    ->label('Manzana')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('lote')
                    ->label('Lote')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('celular')
                    ->label('Celular')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Anotada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('calle')
                    ->label('Calle')
                    ->options(Calle::opciones()),
            ])
            ->defaultSort('numero')
            ->recordActions([
                DeleteAction::make()
                    ->modalHeading('Borrar esta solicitud')
                    ->modalDescription('Sale de la lista. Su folio no se reutiliza y los demás no cambian.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay solicitudes')
            ->emptyStateDescription('Aquí aparecen las propiedades que se anoten desde el formulario de /internet.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesDeInternet::route('/'),
        ];
    }
}
