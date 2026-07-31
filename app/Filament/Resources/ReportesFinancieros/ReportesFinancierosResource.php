<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportesFinancieros;

use App\Filament\Resources\ReportesFinancieros\Pages\CreateReporteFinanciero;
use App\Filament\Resources\ReportesFinancieros\Pages\EditReporteFinanciero;
use App\Filament\Resources\ReportesFinancieros\Pages\ListReportesFinancieros;
use App\Models\ReporteFinanciero as Reporte;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Donde la Mesa Directiva captura los Reportes financieros: el resumen de cifras
 * de cada mes y el enlace a su hoja de cálculo.
 *
 * Fue una pantalla única —un formulario suelto sobre una tabla de un renglón—
 * hasta que el Reporte pasó a conservarse mes por mes (`docs/adr/0005`).
 * Capturar un mes nuevo ya no borra al anterior: lo empuja al archivo, donde
 * queda consultable con su propia dirección. Por eso ahora hay listado.
 *
 * Dos cosas que conviene tener presentes al llenarlo, y por eso están escritas
 * en la pantalla y no solo en la documentación: lo que se guarda aquí sale
 * público y sin contraseña, y las cifras del resumen se capturan a mano —no se
 * leen de la hoja—, así que cuando la hoja cambia, hay que volver aquí.
 */
class ReportesFinancierosResource extends Resource
{
    protected static ?string $model = Reporte::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $slug = 'reporte-financiero';

    protected static ?string $navigationLabel = 'Reporte financiero';

    protected static ?string $modelLabel = 'reporte financiero';

    protected static ?string $pluralModelLabel = 'reportes financieros';

    protected static ?string $recordTitleAttribute = 'periodo';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen de cifras')
                ->description('Las cifras que la Asamblea lee de un vistazo en la página pública. Se capturan a mano: no se leen de la hoja de cálculo.')
                ->schema([
                    Select::make('mes')
                        ->label('Mes que se rinde')
                        ->helperText('Un reporte cubre siempre un mes. De aquí salen el título de la página y su dirección —/reporte-financiero/2026-06—, así que no se escribe a mano.')
                        ->options(fn (?Reporte $record): array => self::mesesQueSePuedenCapturar($record))
                        // El estado guardado llega como fecha; el Select compara
                        // contra las claves de arriba, que son texto.
                        ->formatStateUsing(fn (mixed $state): ?string => blank($state)
                            ? null
                            : CarbonImmutable::parse($state)->startOfMonth()->toDateString())
                        ->default(CarbonImmutable::now()->startOfMonth()->subMonth()->toDateString())
                        ->native(false)
                        ->required()
                        // La base ya lo impide con un índice único; esto es para
                        // que se vea como un aviso del formulario y no como un
                        // error del servidor.
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'Ya hay un reporte de ese mes. Edita ese en vez de capturar uno nuevo.',
                        ]),

                    Repeater::make('cifras')
                        ->label('Cifras')
                        ->helperText('Salen en la página en este mismo orden, como los renglones de un comprobante. Marca la última como total para que se vea destacada.')
                        ->table([
                            TableColumn::make('Concepto'),
                            TableColumn::make('Monto (MXN)')->width('12rem'),
                            TableColumn::make('Total')->width('6rem')->alignCenter(),
                        ])
                        ->schema([
                            TextInput::make('concepto')
                                ->label('Concepto')
                                ->required()
                                ->maxLength(120),

                            TextInput::make('monto')
                                ->label('Monto')
                                ->helperText('En pesos. Un gasto puede ir en negativo.')
                                ->required()
                                ->numeric()
                                ->prefix('$'),

                            Toggle::make('destacada')
                                ->label('Total')
                                ->default(false),
                        ])
                        ->addActionLabel('Agregar una cifra')
                        ->reorderable()
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    Textarea::make('aclaracion')
                        ->label('Aclaración del periodo')
                        ->helperText('Opcional. Lo que las cifras no dicen solas: un ingreso extraordinario que no se repite, un gasto que se adelantó, un mes que no se compara con los demás. Sale destacada arriba de las cifras. Déjala vacía si el resumen se explica solo.')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),

            Section::make('Hoja de cálculo')
                ->description('Donde vive el detalle. Es la fuente de verdad del reporte; el resumen de arriba solo la asoma.')
                ->schema([
                    TextInput::make('hoja_url')
                        ->label('Enlace a la hoja de Google')
                        ->helperText('Pega aquí la URL de «Compartir». Cada mes lleva la suya: el reporte de junio no debe apuntar a la hoja de julio. En la página pública abre en una pestaña nueva.')
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                ]),

            Callout::make('Esto queda público, sin contraseña')
                ->warning()
                ->description('La página del Reporte financiero no pide nada para leerse y los buscadores pueden indexarla. Es una decisión tomada por la Mesa Directiva (docs/adr/0004), no un pendiente: la hoja se comparte por enlace, así que una contraseña en la página no habría protegido la hoja. Y queda público para siempre: los meses anteriores no se retiran, se archivan (docs/adr/0005). No captures aquí nada que no deba ser público.'),

            Callout::make('Revisa cómo está compartida la hoja')
                ->info()
                ->description('Si la hoja está restringida a cuentas invitadas, quien abra el enlace desde el sitio se topa con una pantalla de permiso denegado. Tiene que estar en «Cualquier persona con el enlace: puede ver». Y como las cifras de arriba se capturan a mano, cuando la hoja cambie hay que volver aquí a actualizarlas.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('periodo')
                    ->label('Mes que se rinde')
                    // `periodo` se deriva del mes, así que el orden se pide
                    // sobre la columna que sí existe.
                    ->sortable(['mes'])
                    ->weight('medium'),

                TextColumn::make('vigente')
                    ->label('')
                    ->badge()
                    ->color('primary')
                    // Una consulta por renglón, sobre una tabla que crece doce
                    // renglones al año.
                    ->getStateUsing(fn (Reporte $record): ?string => $record->esVigente() ? 'Vigente' : null),

                TextColumn::make('cifras')
                    ->label('Cifras')
                    ->getStateUsing(fn (Reporte $record): int => $record->resumen()->count()),

                IconColumn::make('hoja_url')
                    ->label('Hoja')
                    ->boolean()
                    ->getStateUsing(fn (Reporte $record): bool => $record->tieneHoja()),
            ])
            // El más reciente arriba: es el que está publicado en la raíz del
            // sitio y el que más se toca.
            ->defaultSort('mes', 'desc')
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->modalHeading('Borrar este reporte')
                    ->modalDescription('Desaparece del sitio, y con él la rendición de cuentas de ese mes. Si era el mes vigente, la página pública pasa a publicar el anterior. No hay forma de recuperarlo.'),
            ])
            // Sin borrado en lote a propósito: aquí cada renglón es la rendición
            // de cuentas de un mes entero, y una casilla mal marcada borra un
            // año de historia sin que nadie lea qué se llevó.
            ->emptyStateHeading('Todavía no hay ningún reporte financiero')
            ->emptyStateDescription('Captura el primer mes. El más reciente que captures es el que se publica en /reporte-financiero; los anteriores quedan consultables con su propia dirección.');
    }

    /**
     * Los meses que el formulario ofrece: los dos años hacia atrás, el mes en
     * curso y el siguiente. Se captura lo que ya se cerró, así que el rango
     * apunta al pasado; el mes que sigue está por si la Mesa Directiva adelanta
     * el registro antes de que termine.
     *
     * @return array<string, string>
     */
    private static function mesesQueSePuedenCapturar(?Reporte $record): array
    {
        $tope = CarbonImmutable::now()->startOfMonth()->addMonth();

        $meses = [];

        for ($i = 0; $i <= 25; $i++) {
            $mes = $tope->subMonths($i);
            $meses[$mes->toDateString()] = ucfirst(Reporte::nombreDelMes($mes));
        }

        // Un reporte viejo tiene que poder editarse aunque su mes ya se haya
        // salido de la ventana de arriba. Sin esto el Select llegaría en blanco
        // y guardar lo cambiaría de mes sin que nadie lo pidiera.
        if ($record?->mes !== null) {
            $meses[$record->mes->toDateString()] = ucfirst(Reporte::nombreDelMes($record->mes));
        }

        // Las claves son 'AAAA-MM-01', así que el orden alfabético inverso es el
        // cronológico inverso: lo más reciente primero, como en el archivo.
        krsort($meses);

        return $meses;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReportesFinancieros::route('/'),
            'create' => CreateReporteFinanciero::route('/nuevo'),
            'edit' => EditReporteFinanciero::route('/{record}/editar'),
        ];
    }
}
