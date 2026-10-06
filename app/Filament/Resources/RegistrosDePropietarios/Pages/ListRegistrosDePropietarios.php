<?php

declare(strict_types=1);

namespace App\Filament\Resources\RegistrosDePropietarios\Pages;

use App\Filament\Resources\RegistrosDePropietarios\RegistrosDePropietariosResource;
use App\Filament\Resources\RegistrosDePropietarios\Widgets\ResumenDeRegistros;
use App\Models\RegistroDePropietario;
use App\Support\Registro\ListaCsv;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListRegistrosDePropietarios extends ListRecords
{
    protected static string $resource = RegistrosDePropietariosResource::class;

    public function getHeading(): string
    {
        return 'Registros de propietarios';
    }

    public function getSubheading(): string
    {
        return 'Lo que los propietarios llenaron en el formulario de registro. El enlace para compartirlo es '.url('/registro').'.';
    }

    protected function getHeaderWidgets(): array
    {
        return [ResumenDeRegistros::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('descargarCsv')
                ->label('Descargar lista (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->descargarCsv()),
        ];
    }

    /**
     * Todos los registros, no solo los que la tabla muestra con su búsqueda: la
     * lista es para llevársela, y una descarga que depende de qué filtro quedó
     * puesto da archivos distintos sin avisar.
     */
    private function descargarCsv(): StreamedResponse
    {
        $registros = RegistroDePropietario::query()
            ->with(['lotes', 'contactos'])
            ->oldest()
            ->get();

        return response()->streamDownload(function () use ($registros): void {
            $flujo = ListaCsv::flujo($registros);
            fpassthru($flujo);
            fclose($flujo);
        }, 'registros-de-propietarios-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
