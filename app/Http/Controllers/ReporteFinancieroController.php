<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReporteFinanciero;
use Illuminate\View\View;

/**
 * La rendición de cuentas económica del Periodo. Pública y sin ninguna barrera,
 * igual que las otras dos páginas — ver `docs/adr/0004`: se consideró ponerla
 * detrás de una clave compartida y se descartó, porque la hoja de cálculo se
 * comparte por enlace y la barrera nunca la habría cubierto.
 */
class ReporteFinancieroController extends Controller
{
    public function index(): View
    {
        return view('pages.reporte-financiero', [
            'reporte' => ReporteFinanciero::actual(),
        ]);
    }
}
