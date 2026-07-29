<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Actividad;
use Illuminate\View\View;

/**
 * La página que sustenta la Propuesta con lo que la Mesa Directiva hizo durante
 * el Periodo. De lectura y sin autenticación, como las otras dos.
 */
class ActividadesController extends Controller
{
    public function index(): View
    {
        return view('pages.actividades', [
            'actividades' => Actividad::recientes()->get(),
        ]);
    }
}
