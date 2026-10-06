<?php

declare(strict_types=1);

namespace App\Filament\Resources\SolicitudesDeInternet\Pages;

use App\Filament\Resources\SolicitudesDeInternet\SolicitudesDeInternetResource;
use Filament\Resources\Pages\ListRecords;

class ListSolicitudesDeInternet extends ListRecords
{
    protected static string $resource = SolicitudesDeInternetResource::class;

    public function getHeading(): string
    {
        return 'Lista de espera de internet';
    }

    public function getSubheading(): string
    {
        return 'Las propiedades que pidieron la instalación, en el orden en que se anotaron. El enlace para compartir el formulario es '.url('/internet').'.';
    }
}
