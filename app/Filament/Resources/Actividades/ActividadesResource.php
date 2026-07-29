<?php

declare(strict_types=1);

namespace App\Filament\Resources\Actividades;

use App\Filament\Resources\Actividades\Pages\CreateActividad;
use App\Filament\Resources\Actividades\Pages\EditActividad;
use App\Filament\Resources\Actividades\Pages\ListActividades;
use App\Models\Actividad;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Alta, edición y borrado de Actividades. A diferencia de los Comentarios ―que
 * escriben los colonos y la Mesa Directiva solo modera―, esto sí lo redacta la
 * Mesa Directiva, así que aquí el CRUD está completo.
 *
 * El formulario tiene dos campos y no debe crecer: ni costo ni adjunto. Ver
 * `App\Models\Actividad` para el porqué de cada ausencia.
 */
class ActividadesResource extends Resource
{
    protected static ?string $model = Actividad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $slug = 'actividades';

    protected static ?string $navigationLabel = 'Actividades';

    protected static ?string $modelLabel = 'actividad';

    protected static ?string $pluralModelLabel = 'actividades';

    protected static ?string $recordTitleAttribute = 'descripcion';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->description('Lo que la Mesa Directiva llevó a cabo durante el Periodo. Se lee tal cual en la página pública.')
                ->schema([
                    DatePicker::make('fecha')
                        ->label('Fecha')
                        ->helperText('El día en que ocurrió. Es lo que ordena la lista pública.')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/M/Y')
                        ->default(now()),

                    Textarea::make('descripcion')
                        ->label('Descripción')
                        ->helperText('Sin cifras de gasto: el dinero se rinde completo en el Reporte financiero.')
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
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/M/Y')
                    ->sortable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->wrap()
                    ->searchable(),
            ])
            // El mismo orden que lee la Asamblea, para que el panel muestre lo
            // que muestra el sitio.
            ->defaultSort('fecha', 'desc')
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->modalHeading('Borrar esta actividad')
                    ->modalDescription('Desaparece de la página pública. No hay forma de recuperarla.'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay actividades')
            ->emptyStateDescription('Cada una que agregues aparece de inmediato en la página pública de Actividades.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActividades::route('/'),
            'create' => CreateActividad::route('/nueva'),
            'edit' => EditActividad::route('/{record}/editar'),
        ];
    }
}
