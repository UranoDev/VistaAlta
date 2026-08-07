<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pendientes;

use App\Filament\Resources\Pendientes\Pages\CreatePendiente;
use App\Filament\Resources\Pendientes\Pages\EditPendiente;
use App\Filament\Resources\Pendientes\Pages\ListPendientes;
use App\Models\Actividad;
use App\Models\Pendiente;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;

/**
 * «Lo que sigue»: la mitad de la página de Actividades que enumera lo que falta.
 * Vivía escrita a mano dentro de la vista, así que corregirla exigía un
 * despliegue; ahora la mantiene la Mesa Directiva, igual que la Bitácora.
 *
 * Dos ausencias deliberadas en el formulario:
 *
 * - **No hay fecha comprometida.** Es la razón de ser de la lista tal como está
 *   escrita en la página: la notaría, la Fraccionadora y los proveedores llevan
 *   su propio paso, y una fecha que no se controla se lee como promesa. Si el
 *   campo existe, se llena.
 * - **No hay casilla de «cumplido».** Un pendiente que se cumple no se marca:
 *   se convierte en Actividad con la acción «Ya se hizo» y se retira.
 */
class PendientesResource extends Resource
{
    protected static ?string $model = Pendiente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $slug = 'pendientes';

    protected static ?string $navigationLabel = 'Lo que sigue';

    protected static ?string $modelLabel = 'pendiente';

    protected static ?string $pluralModelLabel = 'pendientes';

    protected static ?string $recordTitleAttribute = 'titulo';

    /** Justo después de Actividades: en la página son dos mitades de lo mismo. */
    protected static ?int $navigationSort = 45;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->description('Lo que todavía falta por hacer. Se lee tal cual en la página pública de Actividades, debajo de la Bitácora.')
                ->schema([
                    TextInput::make('titulo')
                        ->label('Pendiente')
                        ->helperText('Qué falta, en una línea. Sin fecha: la lista no compromete plazos que dependen de un tercero.')
                        ->required()
                        ->maxLength(160)
                        ->columnSpanFull(),

                    Textarea::make('detalle')
                        ->label('Detalle')
                        ->helperText('Por qué sigue pendiente y qué hace falta para cerrarlo.')
                        ->required()
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titulo')
                    ->label('Pendiente')
                    ->weight('bold')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('detalle')
                    ->label('Detalle')
                    ->wrap()
                    ->searchable(),

                // Solo aparece en el renglón que ya se cumplió. Se queda a la
                // vista mientras siga publicándose tachado, que es la ventana
                // en la que «Deshacer» todavía sirve de algo.
                TextColumn::make('cumplido_en')
                    ->label('Estado')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (): string => 'Ya se hizo')
                    ->placeholder('—'),
            ])
            // El orden es contenido, no preferencia de quien mira: el primer
            // renglón es el pendiente del que cuelgan los demás, y así sale
            // publicado. Se arrastra aquí y se lee igual en el sitio.
            ->reorderable('orden')
            ->defaultSort('orden')
            ->recordActions([
                Action::make('yaSeHizo')
                    ->label('Ya se hizo')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->modalHeading('Pasar este pendiente a la Bitácora')
                    ->modalDescription('Se publica como Actividad con la fecha de hoy y el pendiente sale de «Lo que sigue».')
                    ->modalSubmitActionLabel('Publicar la actividad')
                    ->fillForm(fn (Pendiente $record): array => ['descripcion' => $record->titulo])
                    ->schema([
                        Textarea::make('descripcion')
                            ->label('Descripción de la actividad')
                            // El pendiente dice qué falta y por qué; la
                            // Actividad dice qué se hizo. Copiar el título tal
                            // cual deja una bitácora escrita en futuro, así que
                            // llega precargado pero editable.
                            ->helperText('Viene del pendiente, pero conviene redactarlo como lo que ya se hizo. Sin cifras de gasto: el dinero se rinde en el Reporte financiero.')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000),
                    ])
                    ->visible(fn (Pendiente $record): bool => ! $record->estaCumplido())
                    ->action(function (Pendiente $record, array $data): void {
                        // En una transacción: si algo falla, no queremos el
                        // pendiente marcado sin su Actividad publicada.
                        DB::transaction(function () use ($record, $data): void {
                            Actividad::query()->create([
                                'fecha' => now()->startOfDay(),
                                'descripcion' => $data['descripcion'],
                            ]);

                            // Se marca, no se borra. El renglón tiene que seguir
                            // existiendo para poder publicarse tachado unos días
                            // —y para que esto se pueda deshacer—; pasada la
                            // ventana deja de aparecer solo.
                            $record->forceFill(['cumplido_en' => now()])->save();
                        });

                        Notification::make()
                            ->title('Listo')
                            ->body('Quedó publicada en la Bitácora con la fecha de hoy, y en «Lo que sigue» aparece tachada unos días antes de retirarse.')
                            ->success()
                            ->send();
                    }),

                /*
                 * La vuelta atrás de «Ya se hizo», que hasta ahora no existía:
                 * la acción publica en una página pública de un solo clic, y
                 * equivocarse obligaba a recapturar el pendiente desde cero.
                 *
                 * No toca la Actividad publicada. Son dos hechos distintos —que
                 * el pendiente siga abierto y que se haya publicado algo en la
                 * Bitácora— y borrar contenido publicado sin que nadie lo pida
                 * sería peor que dejarlo: la Actividad se retira desde su propia
                 * pantalla, si es que también estuvo de más.
                 */
                Action::make('deshacerCumplido')
                    ->label('Deshacer')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Pendiente $record): bool => $record->estaCumplido())
                    ->requiresConfirmation()
                    ->modalHeading('Devolver este pendiente a «Lo que sigue»')
                    ->modalDescription('Deja de aparecer tachado y vuelve a contarse como pendiente. La Actividad que se publicó en la Bitácora no se toca: si también estuvo de más, se retira desde Actividades.')
                    ->modalSubmitActionLabel('Devolverlo')
                    ->action(function (Pendiente $record): void {
                        $record->forceFill(['cumplido_en' => null])->save();

                        Notification::make()
                            ->title('Listo')
                            ->body('Vuelve a estar en «Lo que sigue».')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make()
                    ->modalHeading('Borrar este pendiente')
                    ->modalDescription('Desaparece de la página pública sin pasar a la Bitácora. Si ya se hizo, usa «Ya se hizo».'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('No hay pendientes')
            ->emptyStateDescription('La página pública lo dice así en vez de dejar el encabezado colgando.');
    }

    /**
     * El mismo filtro que la página pública: los abiertos más los cumplidos
     * hace poco. Un cumplido viejo ya no le sirve a nadie en esta pantalla —no
     * se publica y no se puede deshacer con provecho—, así que se cae solo de
     * la lista en vez de acumularse.
     */
    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->vigentes();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPendientes::route('/'),
            'create' => CreatePendiente::route('/nuevo'),
            'edit' => EditPendiente::route('/{record}/editar'),
        ];
    }
}
