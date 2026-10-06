<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AnotarseParaInternetRequest;
use App\Models\SolicitudDeInternet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * La lista de espera para la instalación de internet: cada propiedad que quiere
 * el servicio se anota con su domicilio y su celular, y recibe un folio
 * consecutivo (INT-001, INT-002…).
 *
 * Es público y sin cuenta, como el resto del sitio, y se comparte como enlace.
 * No pide OTP: no publica nada, y el celular solo sirve para localizar a quien
 * pidió el servicio. Lo que lo cuida del abuso es un tope por IP en la ruta y un
 * campo trampa.
 *
 * Un mismo celular puede anotar varias propiedades; un mismo domicilio entra una
 * sola vez. Si alguien anota uno que ya está, no se crea otro folio: se le dice
 * cuál tiene.
 */
class InternetController extends Controller
{
    /**
     * El campo trampa: un humano nunca lo ve, así que un valor aquí es un
     * programa llenando todo lo que encuentra.
     */
    private const TRAMPA = 'sitio_web';

    /** El celular de quien acaba de anotar una propiedad, para ahorrarle teclearlo en la siguiente. */
    private const CELULAR = 'internet.celular';

    public function create(Request $peticion): View
    {
        return view('pages.internet', [
            'celular' => $peticion->session()->get(self::CELULAR),
        ]);
    }

    public function store(AnotarseParaInternetRequest $peticion): RedirectResponse
    {
        // A un robot no se le dice nada: vuelve al formulario como si no hubiera pasado.
        if (filled($peticion->input(self::TRAMPA))) {
            return redirect()->route('internet');
        }

        $solicitud = SolicitudDeInternet::anotar($peticion->domicilio(), $peticion->celular());

        $peticion->session()->put(self::CELULAR, $peticion->celular());

        if (! $solicitud->wasRecentlyCreated) {
            return redirect()->route('internet')
                ->withInput()
                ->withErrors(['domicilio' => "Esta propiedad ya está en la lista con el folio {$solicitud->folio()}."]);
        }

        return redirect()->route('internet')->with('internet.listo', [
            'folio' => $solicitud->folio(),
            'domicilio' => $solicitud->domicilio(),
        ]);
    }
}
