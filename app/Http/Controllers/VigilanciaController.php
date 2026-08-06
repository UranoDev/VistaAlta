<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Vigilancia\RolDeVigilancia;
use Illuminate\View\View;

/**
 * Quién cuida el fraccionamiento, en `/vigilancia`. Pública y de lectura, como
 * el resto de la mitad que rinde cuentas.
 *
 * Lleva controlador y no `Route::view` —a diferencia de `/demanda` y las dos
 * legales— porque la página no es estática: depende del reloj, y a las 22:00
 * contesta distinto que a las 21:59.
 */
class VigilanciaController extends Controller
{
    public function index(): View
    {
        $rol = RolDeVigilancia::deLaConfiguracion();
        $momento = RolDeVigilancia::ahora();

        return view('pages.vigilancia', [
            'vigilantes' => $rol->vigilantes(),
            'deGuardia' => $rol->deGuardia($momento),
            'momento' => $momento,
        ]);
    }
}
