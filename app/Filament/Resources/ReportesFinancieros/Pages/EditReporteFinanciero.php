<?php

declare(strict_types=1);

namespace App\Filament\Resources\ReportesFinancieros\Pages;

use App\Filament\Resources\ReportesFinancieros\ReportesFinancierosResource;
use App\Models\ReporteFinanciero as Reporte;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditReporteFinanciero extends EditRecord
{
    protected static string $resource = ReportesFinancierosResource::class;

    public function getHeading(): string
    {
        $reporte = $this->getRecord();

        return 'Reporte de '.($reporte instanceof Reporte ? $reporte->periodo : 'un mes');
    }

    /**
     * El puente a lo que ve el Colono. Esta es la pantalla donde más falta: lo
     * que se captura aquí sale publicado como un comprobante, y el orden de los
     * renglones, cuál quedó destacado y cómo cae la aclaración solo se aprecian
     * en la página pública.
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
     * Apunta a la dirección de este mes en particular, no a la raíz: desde que
     * hay histórico, el reporte que se está editando puede no ser el vigente.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('verPaginaPublica')
                ->label('Ver lo publicado')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->tooltip('Abre este mes en la página pública, en una pestaña nueva. Muestra lo que ya está guardado: si tienes cambios sin guardar, ahí no se ven todavía.')
                ->url(fn (Reporte $record): string => $record->urlPublica(), shouldOpenInNewTab: true),

            DeleteAction::make()
                ->modalHeading('Borrar este reporte')
                ->modalDescription('Desaparece del sitio, y con él la rendición de cuentas de ese mes. Si era el mes vigente, la página pública pasa a publicar el anterior. No hay forma de recuperarlo.'),
        ];
    }
}
