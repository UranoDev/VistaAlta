<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Pendiente;
use Illuminate\View\View;

/**
 * La página que sustenta la Propuesta con lo que la Mesa Directiva hizo durante
 * el Periodo. De lectura y sin autenticación, como las otras dos.
 *
 * Las dos mitades de la página salen de la base y las dos se mantienen desde el
 * panel: la Bitácora de lo hecho y «Lo que sigue» con lo que falta. Que la
 * segunda estuviera escrita en la vista era justo lo que dejaba desactualizada
 * la lista de pendientes hasta el siguiente despliegue.
 */
class ActividadesController extends Controller
{
    public function index(): View
    {
        return view('pages.actividades', [
            'actividades' => Actividad::recientes()->get(),
            'pendientes' => Pendiente::enOrden()->get(),
        ]);
    }
}
