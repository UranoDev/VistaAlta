<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReporteFinanciero;
use Illuminate\View\View;

/**
 * La rendición de cuentas económica del Periodo. Pública y sin ninguna barrera,
 * igual que las otras dos páginas — ver `docs/adr/0004`: se consideró ponerla
 * detrás de una clave compartida y se descartó, porque la hoja de cálculo se
 * comparte por enlace y la barrera nunca la habría cubierto. El histórico no
 * cambia eso: los meses anteriores quedan igual de abiertos (`docs/adr/0005`).
 *
 * Dos entradas a la misma página: `/reporte-financiero`, que siempre publica el
 * mes más reciente, y `/reporte-financiero/2026-06`, que publica ese mes y
 * ninguno otro.
 */
class ReporteFinancieroController extends Controller
{
    public function index(): View
    {
        // Lo que devuelva `actual()` es, por definición, lo vigente — incluso
        // cuando todavía no hay ningún mes capturado y viene vacío.
        return $this->pagina(ReporteFinanciero::actual(), esVigente: true);
    }

    public function mes(string $mes): View
    {
        $reporte = ReporteFinanciero::delMes($mes);

        abort_if($reporte === null, 404);

        return $this->pagina($reporte, esVigente: $reporte->esVigente());
    }

    /**
     * El mes vigente se sirve en dos direcciones —la raíz y la suya con fecha—,
     * así que la página declara cuál de las dos es la buena. Se resuelve con
     * `<link rel="canonical">` y no con una redirección a propósito: la URL con
     * fecha del mes vigente tiene que seguir funcionando igual el día que deje
     * de serlo, y una redirección la haría comportarse distinto según el
     * calendario. Quien guarde el enlace de junio quiere junio, hoy y en
     * diciembre.
     */
    private function pagina(ReporteFinanciero $reporte, bool $esVigente): View
    {
        return view('pages.reporte-financiero', [
            'reporte' => $reporte,
            'esVigente' => $esVigente,
            'meses' => ReporteFinanciero::publicados(),
            'canonical' => $reporte->urlPublica(),
        ]);
    }
}
