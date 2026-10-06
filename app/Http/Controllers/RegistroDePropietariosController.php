<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarPropietarioRequest;
use App\Models\RegistroDePropietario;
use App\Support\Registro\ConfirmacionDelRegistro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * El formulario con el que cada propietario declara sus lotes y sus datos de
 * contacto, para registrar sus pagos en el sistema automatizado, y la
 * confirmación que sigue.
 *
 * Es público y sin cuenta, como el resto del sitio, y se comparte como enlace en
 * el grupo de vecinos. Al enviarlo, el registro queda guardado y se le manda un
 * código (por correo y por WhatsApp) y un enlace (por correo); con cualquiera de
 * los dos el registro queda confirmado. Lo que cuida el formulario del abuso es
 * un tope por IP en la ruta y un campo trampa.
 *
 * El registro pendiente se recuerda en la sesión, no en la dirección: la página
 * de confirmación no lleva ningún dato de nadie en la barra, y quien llega con
 * el enlace del correo desde otro aparato no necesita sesión.
 */
class RegistroDePropietariosController extends Controller
{
    /**
     * El campo trampa: un humano nunca lo ve, así que un valor aquí es un
     * programa llenando todo lo que encuentra.
     */
    private const TRAMPA = 'sitio_web';

    private const PENDIENTE = 'registro.pendiente';

    private const CANALES = 'registro.canales';

    public function __construct(private readonly ConfirmacionDelRegistro $confirmacion) {}

    public function create(Request $peticion): View
    {
        return view('pages.registro', [
            'lotes' => $this->renglonesPrevios($peticion, 'lotes') ?: [[]],
            'contactos' => $this->renglonesPrevios($peticion, 'contactos'),
        ]);
    }

    public function store(RegistrarPropietarioRequest $peticion): RedirectResponse
    {
        // A un robot se le contesta como si hubiera funcionado: decirle que lo
        // detectamos solo le enseña qué cambiar. El 0 no es ningún registro.
        if (filled($peticion->input(self::TRAMPA))) {
            $peticion->session()->put([self::PENDIENTE => 0, self::CANALES => ['correo']]);

            return redirect()->route('registro.confirmar');
        }

        $registro = RegistroDePropietario::registrar(
            $peticion->datosDelPropietario(),
            $peticion->lotes(),
            $peticion->contactos(),
        );

        $canales = $this->confirmacion->emitir($registro);

        $peticion->session()->put([self::PENDIENTE => $registro->id, self::CANALES => $canales]);

        return redirect()->route('registro.confirmar');
    }

    /**
     * La pantalla donde se escribe el código. Si ya se confirmó, lo dice.
     */
    public function confirmar(Request $peticion): View|RedirectResponse
    {
        $id = $peticion->session()->get(self::PENDIENTE);

        if ($id === null) {
            return redirect()->route('registro');
        }

        $registro = RegistroDePropietario::query()->find($id);

        return view('pages.registro-confirmacion', [
            'estado' => $registro?->estaConfirmado() ? 'confirmado' : 'pendiente',
            'canales' => (array) $peticion->session()->get(self::CANALES, []),
            'destinos' => $registro ? $this->destinos($registro) : ['correo' => null, 'telefono' => null],
        ]);
    }

    public function validarCodigo(Request $peticion): RedirectResponse
    {
        $datos = $peticion->validate(['codigo' => ['required', 'digits:6']], [
            'codigo.required' => 'Escribe el código de 6 dígitos.',
            'codigo.digits' => 'El código tiene 6 dígitos.',
        ]);

        $registro = $this->pendiente($peticion);

        if ($registro === null) {
            return redirect()->route('registro');
        }

        if (! $this->confirmacion->confirmarConCodigo($registro, $datos['codigo'])) {
            return redirect()->route('registro.confirmar')
                ->withErrors(['codigo' => 'El código es incorrecto o ya venció. Si ya lo intentaste varias veces, pide uno nuevo.']);
        }

        return redirect()->route('registro.confirmar');
    }

    public function reenviar(Request $peticion): RedirectResponse
    {
        $registro = $this->pendiente($peticion);

        if ($registro === null) {
            return redirect()->route('registro');
        }

        if ($registro->estaConfirmado()) {
            return redirect()->route('registro.confirmar');
        }

        $espera = ConfirmacionDelRegistro::ESPERA_ENTRE_ENVIOS;
        $faltan = $registro->confirmacion_enviada_en
            ? $espera - (int) $registro->confirmacion_enviada_en->diffInSeconds(now(), true)
            : 0;

        if ($faltan > 0) {
            return redirect()->route('registro.confirmar')
                ->withErrors(['reenvio' => "Espera {$faltan} segundos para pedir otro código."]);
        }

        $canales = $this->confirmacion->emitir($registro);

        $peticion->session()->put(self::CANALES, $canales);

        return redirect()->route('registro.confirmar')->with('registro.info', 'Te mandamos un código nuevo. El anterior ya no sirve.');
    }

    /**
     * La página a la que lleva el enlace del correo. **No confirma**: muestra un
     * botón. Los antivirus y los previsualizadores abren cada enlace de un
     * mensaje, y un GET que confirmara dejaría confirmados registros que nadie
     * revisó.
     */
    public function enlace(RegistroDePropietario $registro, string $token): View|Response
    {
        if (! $this->confirmacion->enlaceCorresponde($registro, $token)) {
            return response()->view('pages.registro-confirmacion', ['estado' => 'enlace-invalido'], 404);
        }

        $estado = match (true) {
            $registro->estaConfirmado() => 'confirmado',
            $registro->confirmacionVencida() => 'enlace-vencido',
            default => 'enlace',
        };

        return view('pages.registro-confirmacion', [
            'estado' => $estado,
            'accion' => route('registro.enlace.confirmar', ['registro' => $registro->id, 'token' => $token]),
        ]);
    }

    public function confirmarEnlace(RegistroDePropietario $registro, string $token): RedirectResponse
    {
        $this->confirmacion->confirmarConEnlace($registro, $token);

        return redirect()->route('registro.enlace', ['registro' => $registro->id, 'token' => $token]);
    }

    private function pendiente(Request $peticion): ?RegistroDePropietario
    {
        $id = $peticion->session()->get(self::PENDIENTE);

        return $id ? RegistroDePropietario::query()->find($id) : null;
    }

    /**
     * A dónde se mandó, sin enseñarlo completo: la pantalla la puede estar viendo
     * otra persona en el mismo celular.
     *
     * @return array{correo: ?string, telefono: ?string}
     */
    private function destinos(RegistroDePropietario $registro): array
    {
        $correo = null;

        if (filled($registro->correo)) {
            [$usuario, $dominio] = explode('@', $registro->correo, 2) + [1 => ''];
            $correo = mb_substr($usuario, 0, 2).'***@'.$dominio;
        }

        return [
            'correo' => $correo,
            'telefono' => filled($registro->telefono) ? 'terminación '.substr($registro->telefono, -4) : null,
        ];
    }

    /**
     * Los renglones que el propietario ya había llenado cuando la validación lo
     * regresó al formulario. Se conservan con su índice, huecos incluidos, para
     * que cada error caiga en el renglón que le toca.
     *
     * @return array<int|string, array<string, mixed>>
     */
    private function renglonesPrevios(Request $peticion, string $lista): array
    {
        $previos = $peticion->old($lista, []);

        return is_array($previos) ? array_filter($previos, 'is_array') : [];
    }
}
