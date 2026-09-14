<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Administracion\Organos;
use Illuminate\View\View;

/**
 * Quiénes sirven al fraccionamiento, en `/administracion`. Pública y de
 * lectura, como el resto de la mitad que rinde cuentas.
 *
 * Lleva controlador y no `Route::view` —a diferencia de `/demanda` y las dos
 * legales, que también son estáticas— no porque cambie con el reloj, sino
 * porque la página arma objetos a partir de `config/contenido.php`: los siete
 * integrantes de los dos órganos. Ese armado no va dentro del blade, donde un
 * dato mal capturado revienta en el peor lugar para entender por qué.
 *
 * El trámite sí pasa como arreglo tal cual: son cuatro renglones de texto con
 * su estado, sin nada que derivar, y envolverlos en un objeto no compraría
 * nada.
 */
class AdministracionController extends Controller
{
    public function index(): View
    {
        $organos = Organos::deLaConfiguracion();

        return view('pages.administracion', [
            'razonSocial' => config('contenido.administracion.razon_social'),
            'tramite' => config('contenido.administracion.tramite', []),
            'comite' => $organos->comite(),
            'cabeza' => $organos->cabeza(),
            'integrantes' => $organos->integrantes(),
            'cargos' => $organos->cargos(),
        ]);
    }
}
