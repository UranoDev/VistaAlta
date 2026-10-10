<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\LimiteDeEnvioDeOtpExcedido;
use App\Http\Requests\RegistrarPropietarioRequest;
use App\Models\RegistroDePropietario;
use App\Support\Registro\ConfirmacionDelRegistro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * El formulario con el que cada propietario declara sus lotes y sus datos de
 * contacto, para registrar sus pagos en el sistema automatizado, y las dos
 * verificaciones que siguen: la del correo y la del celular.
 *
 * Es público y sin cuenta, como el resto del sitio, y se comparte como enlace en
 * el grupo de vecinos. Al enviarlo, el registro queda guardado y se le manda un
 * código por cada medio que dio —correo (con un enlace) y celular (por SMS)—;
 * con uno de los dos basta para registrarse. Son **verificaciones separadas**: cada una se escribe por su lado y cada una deja su propia marca
 * en la base. Lo que cuida el formulario del abuso es un tope por IP en la ruta,
 * un campo trampa y, para el SMS, el tope de envíos de `LimiteDeEnvioDeOtp`.
 *
 * El registro pendiente se recuerda en la sesión, no en la dirección: la página
 * de verificación no lleva ningún dato de nadie en la barra. Quien llega con el
 * enlace del correo desde otro aparato no tiene sesión; al tocar el botón se le
 * abre una, para que pueda seguir con el SMS.
 */
class RegistroDePropietariosController extends Controller
{
    /**
     * El campo trampa: un humano nunca lo ve, así que un valor aquí es un
     * programa llenando todo lo que encuentra.
     */
    private const TRAMPA = 'sitio_web';

    private const PENDIENTE = 'registro.pendiente';

    private const ENVIADOS = 'registro.enviados';

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
            $peticion->session()->put([self::PENDIENTE => 0, self::ENVIADOS => ['correo' => true, 'telefono' => true]]);

            return redirect()->route('registro.confirmar');
        }

        $registro = RegistroDePropietario::registrar(
            $peticion->datosDelPropietario(),
            $peticion->lotes(),
            $peticion->contactos(),
        );

        $peticion->session()->put([
            self::PENDIENTE => $registro->id,
            self::ENVIADOS => $this->confirmacion->emitir($registro),
        ]);

        return redirect()->route('registro.confirmar');
    }

    /**
     * La pantalla de las dos verificaciones, cada una con su código.
     */
    public function confirmar(Request $peticion): View|RedirectResponse
    {
        $id = $peticion->session()->get(self::PENDIENTE);

        if ($id === null) {
            return redirect()->route('registro');
        }

        // El 0 es el registro falso del campo trampa: se pinta la pantalla sin más.
        $registro = $id === 0 ? null : RegistroDePropietario::query()->find($id);

        if ($id !== 0 && $registro === null) {
            return $this->sinRegistroPendiente();
        }

        return view('pages.registro-confirmacion', [
            'estado' => 'pendiente',
            // Solo los medios que la persona dio: el que no dio no se verifica.
            'medios' => $registro
                ? array_keys(array_filter(['correo' => $registro->tieneCorreo(), 'telefono' => $registro->tieneTelefono()]))
                : ['correo', 'telefono'],
            'verificado' => [
                'correo' => $registro?->correoVerificado() ?? false,
                'telefono' => $registro?->telefonoVerificado() ?? false,
            ],
            'enviados' => $peticion->session()->get(self::ENVIADOS, ['correo' => true, 'telefono' => true]),
            'destinos' => $registro ? $this->destinos($registro) : ['correo' => null, 'telefono' => null],
        ]);
    }

    /**
     * Verifica un medio con el código que la persona escribió. El otro medio no
     * se toca: son dos verificaciones separadas.
     */
    public function validarCodigo(Request $peticion, string $canal): RedirectResponse
    {
        $validador = Validator::make($peticion->all(), ['codigo' => ['required', 'digits:6']], [
            'codigo.required' => 'Escribe el código de 6 dígitos.',
            'codigo.digits' => 'El código tiene 6 dígitos.',
        ]);

        if ($validador->fails()) {
            return redirect()->route('registro.confirmar')->withErrors(["codigo_{$canal}" => $validador->errors()->first('codigo')]);
        }

        $registro = $this->pendiente($peticion);

        if ($registro === null) {
            return $this->sinRegistroPendiente();
        }

        if (! $this->diceTenerElMedio($registro, $canal)) {
            return redirect()->route('registro.confirmar');
        }

        $codigo = (string) $validador->validated()['codigo'];

        $bien = $canal === 'correo'
            ? $this->confirmacion->verificarCorreoConCodigo($registro, $codigo)
            : $this->confirmacion->verificarTelefonoConCodigo($registro, $codigo);

        if (! $bien) {
            return redirect()->route('registro.confirmar')->withErrors([
                "codigo_{$canal}" => 'El código es incorrecto o ya venció. Si ya lo intentaste varias veces, pide uno nuevo.',
            ]);
        }

        return redirect()->route('registro.confirmar');
    }

    /**
     * Manda otro código por un medio. El anterior de ese medio deja de servir.
     */
    public function reenviar(Request $peticion, string $canal): RedirectResponse
    {
        $registro = $this->pendiente($peticion);

        if ($registro === null) {
            return $this->sinRegistroPendiente();
        }

        $yaVerificado = $canal === 'correo' ? $registro->correoVerificado() : $registro->telefonoVerificado();

        if ($yaVerificado || ! $this->diceTenerElMedio($registro, $canal)) {
            return redirect()->route('registro.confirmar');
        }

        $espera = $this->confirmacion->esperaParaReenviar($registro, $canal);

        if ($espera > 0) {
            return redirect()->route('registro.confirmar')
                ->withErrors(["reenvio_{$canal}" => "Espera {$espera} segundos para pedir otro código."]);
        }

        try {
            $salio = $canal === 'correo'
                ? $this->confirmacion->emitirCorreo($registro)
                : $this->confirmacion->emitirTelefono($registro);
        } catch (LimiteDeEnvioDeOtpExcedido $e) {
            $minutos = max(1, (int) ceil($e->segundosRestantes / 60));

            return redirect()->route('registro.confirmar')
                ->withErrors(["reenvio_{$canal}" => "Pediste demasiados códigos. Intenta de nuevo en {$minutos} minutos."]);
        }

        $peticion->session()->put(self::ENVIADOS, [...$peticion->session()->get(self::ENVIADOS, []), $canal => $salio]);

        return redirect()->route('registro.confirmar')->with(
            'registro.info',
            $canal === 'correo'
                ? 'Te mandamos un código nuevo por correo. El anterior ya no sirve.'
                : 'Te mandamos un código nuevo por SMS. El anterior ya no sirve.',
        );
    }

    /**
     * La página a la que lleva el enlace del correo. **No verifica**: muestra un
     * botón. Los antivirus y los previsualizadores abren cada enlace de un
     * mensaje, y un GET que verificara dejaría verificados correos que nadie
     * tocó.
     */
    public function enlace(Request $peticion, RegistroDePropietario $registro, string $token): View|Response|RedirectResponse
    {
        if (! $this->confirmacion->enlaceCorresponde($registro, $token)) {
            return response()->view('pages.registro-confirmacion', ['estado' => 'enlace-invalido'], 404);
        }

        // El correo ya estaba verificado: se sigue con lo que falta.
        if ($registro->correoVerificado()) {
            return $this->seguirConElRegistro($peticion, $registro);
        }

        if ($registro->correoVencido()) {
            return view('pages.registro-confirmacion', ['estado' => 'enlace-vencido']);
        }

        return view('pages.registro-confirmacion', [
            'estado' => 'enlace',
            'accion' => route('registro.enlace.confirmar', ['registro' => $registro->id, 'token' => $token]),
        ]);
    }

    public function confirmarEnlace(Request $peticion, RegistroDePropietario $registro, string $token): RedirectResponse
    {
        if (! $this->confirmacion->verificarCorreoConEnlace($registro, $token)) {
            return redirect()->route('registro.enlace', ['registro' => $registro->id, 'token' => $token]);
        }

        return $this->seguirConElRegistro($peticion, $registro);
    }

    /**
     * Quien tocó el enlace del correo demostró que controla ese correo, y con eso
     * se le abre la pantalla de este registro: aunque haya llegado desde otro
     * aparato, ahí le queda la verificación del celular por hacer.
     */
    private function seguirConElRegistro(Request $peticion, RegistroDePropietario $registro): RedirectResponse
    {
        $peticion->session()->put([
            self::PENDIENTE => $registro->id,
            self::ENVIADOS => ['correo' => true, 'telefono' => true],
        ]);

        return redirect()->route('registro.confirmar');
    }

    /**
     * El registro que se estaba verificando ya no existe —lo borraron del panel— o
     * la sesión venció. Mandar de vuelta al formulario sin decir nada deja a quien
     * escribió su código sin saber si salió bien, así que se le explica.
     */
    private function sinRegistroPendiente(): RedirectResponse
    {
        return redirect()->route('registro')
            ->with('registro.aviso', 'No encontramos tu registro: se borró o tu sesión venció. Llena el formulario otra vez.');
    }

    /**
     * Si la persona dio ese medio. Quien dio solo el correo no tiene celular que
     * verificar, y pedirlo por la ruta mandaría un SMS a nadie.
     */
    private function diceTenerElMedio(RegistroDePropietario $registro, string $canal): bool
    {
        return $canal === 'correo' ? $registro->tieneCorreo() : $registro->tieneTelefono();
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

        if ($registro->tieneCorreo()) {
            [$usuario, $dominio] = explode('@', $registro->correo, 2) + [1 => ''];
            $correo = mb_substr($usuario, 0, 2).'***@'.$dominio;
        }

        return [
            'correo' => $correo,
            'telefono' => $registro->tieneTelefono() ? 'terminación '.substr($registro->telefono, -4) : null,
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
