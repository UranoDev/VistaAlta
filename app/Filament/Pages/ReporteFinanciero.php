<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\ReporteFinanciero as Reporte;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Donde la Mesa Directiva captura el Reporte financiero del Periodo: el resumen
 * de cifras y el enlace a la hoja de cálculo.
 *
 * Dos cosas que conviene tener presentes al llenarla, y por eso están escritas
 * en la pantalla y no solo en la documentación: lo que se guarda aquí sale
 * público y sin contraseña, y las cifras del resumen se capturan a mano —no se
 * leen de la hoja—, así que cuando la hoja cambia, hay que volver aquí.
 *
 * @property-read Schema $form
 */
class ReporteFinanciero extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Reporte financiero';

    protected static ?string $title = 'Reporte financiero';

    protected static ?string $slug = 'reporte-financiero';

    protected static ?int $navigationSort = 50;

    /**
     * El estado del formulario mientras dura la petición de Livewire.
     *
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * El puente a lo que ve el Colono. Esta es la pantalla donde más falta:
     * lo que se captura aquí sale publicado como un comprobante, y el orden de
     * los renglones, cuál quedó destacado y cómo cae la aclaración solo se
     * aprecian en la página pública.
     *
     * Va arriba porque el formulario crece con cada cifra, y un enlace al pie
     * queda fuera de vista justo cuando se está trabajando en la parte alta de
     * la lista. Y va en gris, sin peso: la acción principal sigue siendo
     * Guardar.
     *
     * El botón **no guarda antes de abrir**, a propósito. La página pública
     * muestra lo guardado, así que lo honesto es decirlo —de ahí la etiqueta y
     * el tooltip— y no publicar por su cuenta lo que nadie pidió publicar: lo
     * que se guarda aquí sale sin contraseña (docs/adr/0004), y esa decisión la
     * toma quien captura, no un atajo de navegación.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('verPaginaPublica')
                ->label('Ver lo publicado')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->tooltip('Abre la página pública en una pestaña nueva. Muestra lo que ya está guardado: si tienes cambios sin guardar, ahí no se ven todavía.')
                ->url(route('reporte-financiero'), shouldOpenInNewTab: true),
        ];
    }

    public function mount(): void
    {
        $reporte = Reporte::actual();

        $this->form->fill([
            'periodo' => $reporte->periodo,
            'cifras' => $reporte->cifras ?? [],
            'aclaracion' => $reporte->aclaracion,
            'hoja_url' => $reporte->hoja_url,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen de cifras')
                    ->description('Las cifras que la Asamblea lee de un vistazo en la página pública. Se capturan a mano: no se leen de la hoja de cálculo.')
                    ->schema([
                        TextInput::make('periodo')
                            ->label('Periodo que cubre')
                            ->helperText('El tramo de tiempo que se está rindiendo, tal como quieres que se lea. Por ejemplo: «Abril – Junio 2026».')
                            ->maxLength(120),

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
                            ->helperText('Pega aquí la URL de «Compartir». En la página pública abre en una pestaña nueva.')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                    ]),

                Callout::make('Esto queda público, sin contraseña')
                    ->warning()
                    ->description('La página del Reporte financiero no pide nada para leerse y los buscadores pueden indexarla. Es una decisión tomada por la Mesa Directiva (docs/adr/0004), no un pendiente: la hoja se comparte por enlace, así que una contraseña en la página no habría protegido la hoja. No captures aquí nada que no deba ser público.'),

                Callout::make('Revisa cómo está compartida la hoja')
                    ->info()
                    ->description('Si la hoja está restringida a cuentas invitadas, quien abra el enlace desde el sitio se topa con una pantalla de permiso denegado. Tiene que estar en «Cualquier persona con el enlace: puede ver». Y como las cifras de arriba se capturan a mano, cuando la hoja cambie hay que volver aquí a actualizarlas.'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $datos = $this->form->getState();

        $reporte = Reporte::actual();
        $reporte->fill($datos);
        $reporte->save();

        Notification::make()
            ->title('Reporte financiero actualizado')
            ->body('Ya es lo que se ve en la página pública.')
            ->success()
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Guardar')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }
}
