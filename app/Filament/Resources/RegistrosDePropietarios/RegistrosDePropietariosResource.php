<?php

declare(strict_types=1);

namespace App\Filament\Resources\RegistrosDePropietarios;

use App\Enums\Calle;
use App\Enums\MedioDeConfirmacion;
use App\Filament\Resources\RegistrosDePropietarios\Pages\ListRegistrosDePropietarios;
use App\Models\LoteRegistrado;
use App\Models\RegistroDePropietario;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lo que los propietarios llenaron en `/registro`: de quién es cada lote y cómo
 * localizarlo, para registrar sus pagos en el sistema automatizado.
 *
 * Es de lectura: no hay «nuevo» ni «editar». Un registro es lo que la persona
 * declaró, y corregirlo a mano en el panel lo vuelve la versión de quien lo
 * corrigió. Lo que sí se puede es borrarlo (un duplicado, una captura de
 * broma) y descargar la lista.
 *
 * Es la pantalla con más datos personales del panel —teléfonos, correos, lotes—,
 * y no se publica en ninguna parte del sitio.
 */
class RegistrosDePropietariosResource extends Resource
{
    protected static ?string $model = RegistroDePropietario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $slug = 'registros';

    protected static ?string $navigationLabel = 'Registros';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'registros';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?int $navigationSort = 60;

    /**
     * Cuántos registros hay por validar, junto al nombre en el menú: es lo que le
     * toca hacer a la Administración.
     */
    public static function getNavigationBadge(): ?string
    {
        $porValidar = RegistroDePropietario::query()->porValidar()->count();

        return $porValidar > 0 ? (string) $porValidar : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Propietario')
                ->columns(2)
                ->schema([
                    TextEntry::make('nombre')->label('Nombre')->weight('bold'),
                    TextEntry::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i'),
                    TextEntry::make('telefono')->label('Celular')->placeholder('No lo dio')->copyable(),
                    TextEntry::make('correo')->label('Correo')->placeholder('No lo dio')->copyable(),
                ]),

            Section::make('Lotes')
                ->schema([
                    RepeatableEntry::make('lotes')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('calle')->label('Calle y número')
                                ->state(fn (LoteRegistrado $lote): string => $lote->etiqueta())
                                ->weight('bold'),
                            TextEntry::make('manzana')->label('Manzana')->placeholder('—'),
                            TextEntry::make('lote')->label('Lote')->placeholder('—'),
                            TextEntry::make('situacion')->label('Situación')
                                ->state(fn (LoteRegistrado $lote): string => $lote->situacion->etiqueta()),
                        ]),
                ]),

            Section::make('Otros contactos')
                ->visible(fn (?RegistroDePropietario $record): bool => $record?->contactos->isNotEmpty() ?? false)
                ->schema([
                    RepeatableEntry::make('contactos')
                        ->hiddenLabel()
                        ->columns(3)
                        ->schema([
                            TextEntry::make('nombre')->label('Nombre')->weight('bold'),
                            TextEntry::make('telefono')->label('Teléfono')->placeholder('—')->copyable(),
                            TextEntry::make('correo')->label('Correo')->placeholder('—')->copyable(),
                        ]),
                ]),

            Section::make('Emergencia y residentes')
                ->columns(3)
                ->schema([
                    TextEntry::make('emergencia_nombre')->label('Contacto de emergencia')->placeholder('Sin captura'),
                    TextEntry::make('emergencia_telefono')->label('Teléfono')->placeholder('—')->copyable(),
                    TextEntry::make('residentes')->label('Residentes')->placeholder('—'),
                ]),

            // Dos verificaciones separadas: cada medio tiene su propia marca.
            Section::make('Verificación')
                ->description('Con el correo o el celular basta para registrarse. Cada uno que se dio se verifica por separado, con su propio código.')
                ->columns(2)
                ->schema([
                    TextEntry::make('correo_verificado_en')->label('Correo verificado')
                        ->state(fn (RegistroDePropietario $record): string => ! $record->tieneCorreo()
                            ? 'No lo dio'
                            : ($record->correoVerificado() ? $record->correo_verificado_en->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Pendiente'))
                        ->helperText(fn (?RegistroDePropietario $record): ?string => match ($record?->correo_verificado_por) {
                            MedioDeConfirmacion::Enlace => 'Con el enlace del correo.',
                            MedioDeConfirmacion::Codigo => 'Con el código.',
                            default => null,
                        }),
                    TextEntry::make('telefono_verificado_en')->label('Celular verificado')
                        ->state(fn (RegistroDePropietario $record): string => ! $record->tieneTelefono()
                            ? 'No lo dio'
                            : ($record->telefonoVerificado() ? $record->telefono_verificado_en->timezone(config('app.timezone'))->format('d/m/Y H:i') : 'Pendiente'))
                        ->helperText(fn (?RegistroDePropietario $record): ?string => $record?->telefonoVerificado() ? 'Con el código del SMS.' : null),
                ]),

            Section::make('Validación de la Administración')
                ->description('Verificar prueba que la persona controla su correo y su celular. Validar es que la Administración revisó que el registro es cierto.')
                ->columns(2)
                ->schema([
                    TextEntry::make('validado_en')->label('Validado')->dateTime('d/m/Y H:i')->placeholder('Por validar'),
                    TextEntry::make('validador.name')->label('Lo validó')->placeholder('—'),
                    TextEntry::make('validacion_nota')->label('Cómo se revisó')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Consentimiento')
                ->columns(2)
                ->schema([
                    TextEntry::make('aceptado_en')->label('Aceptó el aviso de privacidad')->dateTime('d/m/Y H:i'),
                    TextEntry::make('aviso_version')->label('Versión del aviso'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('nombre')
                    ->label('Propietario')
                    ->weight('bold')
                    ->wrap()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lotes')
                    ->label('Lotes')
                    ->state(fn (RegistroDePropietario $registro): array => $registro->lotes
                        ->map(fn (LoteRegistrado $lote): string => implode(' · ', array_filter([$lote->etiqueta(), $lote->ubicacion()])))
                        ->all())
                    ->listWithLineBreaks()
                    // Buscar «Margarita 128» o solo «128»: la búsqueda entra por la
                    // tabla de lotes, que es donde viven esos datos, y cada palabra
                    // tiene que caer en la calle o en el número.
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $palabras = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                        return $query->orWhereHas('lotes', function (Builder $lotes) use ($palabras): void {
                            foreach ($palabras as $palabra) {
                                $lotes->where(fn (Builder $q): Builder => $q
                                    ->where('calle', 'like', "%{$palabra}%")
                                    ->orWhere('numero_oficial', 'like', "%{$palabra}%"));
                            }
                        });
                    }),

                TextColumn::make('telefono')
                    ->label('Contacto')
                    ->placeholder('Sin celular')
                    ->description(fn (RegistroDePropietario $registro): string => $registro->correo ?? 'Sin correo')
                    ->searchable(['telefono', 'correo']),

                IconColumn::make('correo_verificado_en')
                    ->label('Correo')
                    ->boolean()
                    // Nulo cuando no dio correo: ahí no hay nada que verificar y no se
                    // pinta ni la palomita ni la tacha.
                    ->state(fn (RegistroDePropietario $registro): ?bool => $registro->tieneCorreo() ? $registro->correoVerificado() : null)
                    ->tooltip(fn (RegistroDePropietario $registro): string => ! $registro->tieneCorreo()
                        ? 'No dio correo'
                        : ($registro->correoVerificado()
                            ? 'Correo verificado el '.$registro->correo_verificado_en->timezone(config('app.timezone'))->format('d/m/Y H:i')
                            : 'Correo sin verificar')),

                IconColumn::make('telefono_verificado_en')
                    ->label('Celular')
                    ->boolean()
                    ->state(fn (RegistroDePropietario $registro): ?bool => $registro->tieneTelefono() ? $registro->telefonoVerificado() : null)
                    ->tooltip(fn (RegistroDePropietario $registro): string => ! $registro->tieneTelefono()
                        ? 'No dio celular'
                        : ($registro->telefonoVerificado()
                            ? 'Celular verificado el '.$registro->telefono_verificado_en->timezone(config('app.timezone'))->format('d/m/Y H:i')
                            : 'Celular sin verificar')),

                IconColumn::make('validado_en')
                    ->label('Validado')
                    ->boolean()
                    ->state(fn (RegistroDePropietario $registro): bool => $registro->estaValidado())
                    ->tooltip(fn (RegistroDePropietario $registro): string => $registro->estaValidado()
                        ? 'Validado el '.$registro->validado_en->timezone(config('app.timezone'))->format('d/m/Y H:i')
                        : 'Sin validar'),

                TextColumn::make('contactos_count')
                    ->label('Otros contactos')
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Ninguno' : ($state === 1 ? '1 persona' : "{$state} personas"))
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'primary'),

                TextColumn::make('emergencia_nombre')
                    ->label('Emergencia')
                    ->placeholder('Sin captura')
                    ->description(fn (RegistroDePropietario $registro): ?string => $registro->emergencia_telefono),

                TextColumn::make('residentes')
                    ->label('Residentes')
                    ->placeholder('—')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('calle')
                    ->label('Calle')
                    ->options(Calle::opciones())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('lotes', fn (Builder $q): Builder => $q->where('calle', $data['value']))
                        : $query),

                SelectFilter::make('verificacion')
                    ->label('Verificación')
                    ->options([
                        'completa' => 'Todo lo que dieron, verificado',
                        'falta' => 'Falta verificar algo',
                        'correo_pendiente' => 'Correo por verificar',
                        'celular_pendiente' => 'Celular por verificar',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'completa' => $query->verificados(),
                        'falta' => $query->whereNotIn('id', RegistroDePropietario::query()->verificados()->select('id')),
                        'correo_pendiente' => $query->whereNotNull('correo')->whereNull('correo_verificado_en'),
                        'celular_pendiente' => $query->whereNotNull('telefono')->whereNull('telefono_verificado_en'),
                        default => $query,
                    }),

                TernaryFilter::make('validado_en')
                    ->label('Validación')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Validados')
                    ->falseLabel('Sin validar'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->label('Ver')->slideOver(),

                // Solo con el correo y el celular ya verificados: validar un registro
                // cuyo contacto no se pudo comprobar no tendría a quién avisarle.
                Action::make('validar')
                    ->label('Validar')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (RegistroDePropietario $record): bool => $record->estaVerificado() && ! $record->estaValidado())
                    ->modalHeading('Validar este registro')
                    ->modalDescription('Queda marcado que la Administración revisó que el registro es cierto, con tu nombre y la fecha de hoy.')
                    ->modalSubmitActionLabel('Validar')
                    ->schema([
                        Textarea::make('nota')
                            ->label('Cómo se revisó (opcional)')
                            ->helperText('Por ejemplo: «Escritura mostrada el 12 de octubre». No se publica.')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (RegistroDePropietario $record, array $data): void {
                        $record->validar(auth()->user(), $data['nota'] ?? null);

                        Notification::make()->title('Registro validado')->success()->send();
                    }),

                Action::make('quitarValidacion')
                    ->label('Quitar validación')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (RegistroDePropietario $record): bool => $record->estaValidado())
                    ->requiresConfirmation()
                    ->modalHeading('Quitar la validación')
                    ->modalDescription('El registro vuelve a quedar por validar. Se borra la nota de cómo se revisó.')
                    ->modalSubmitActionLabel('Quitar')
                    ->action(function (RegistroDePropietario $record): void {
                        $record->quitarValidacion();

                        Notification::make()->title('Listo')->body('El registro quedó por validar.')->success()->send();
                    }),

                DeleteAction::make()
                    ->modalHeading('Borrar este registro')
                    ->modalDescription('Se borra el registro con sus lotes y sus contactos. No hay forma de recuperarlo.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay registros')
            ->emptyStateDescription('Aquí aparecen los registros que lleguen desde el formulario de /registro.');
    }

    /**
     * Con los lotes cargados de antemano y los contactos contados: sin esto la
     * tabla haría una consulta por renglón.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['lotes', 'validador'])->withCount('contactos');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrosDePropietarios::route('/'),
        ];
    }
}
