<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Visibilidad;
use App\Models\Comentario;
use App\Models\RecepcionDeComentarios;
use App\Models\ViaDeRecepcion;
use App\Support\Otp\OtpService;
use App\Support\VentanaDeValidacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * La página de la Propuesta: el video, las preguntas frecuentes, la lista de
 * Comentarios públicos ya publicados y el formulario para dejar uno.
 *
 * Portado de `AsociacionCivilController` de nvavista (ver docs/adr/0003), sin
 * tenancy y con lo que allá no existe: Ventana de validación de 30 minutos,
 * elección de visibilidad, la lista de comentarios y el respeto a los dos
 * interruptores del panel —la Recepción de comentarios, que decide si se admiten
 * comentarios nuevos, y la Vía de recepción, que decide por dónde llegan—.
 */
class PropuestaController extends Controller
{
    private const PROPOSITO = 'comentario';

    private const RECEPCION_CERRADA = 'La Mesa Directiva cerró la recepción de comentarios. Lo que ya se publicó sigue aquí.';

    private const RECEPCION_POR_WHATSAPP = 'Ahora los comentarios se reciben por WhatsApp. Escríbele a la Mesa Directiva desde el enlace de esta página.';

    public function __construct(
        private readonly OtpService $otp,
        private readonly VentanaDeValidacion $ventana,
    ) {}

    public function index(Request $peticion): View
    {
        return view('pages.propuesta', [
            'videoUrl' => config('services.asociacion_civil.video_url'),
            'preguntasFrecuentes' => config('contenido.preguntas_frecuentes', []),
            'comentariosPublicos' => Comentario::publicados()->get(),
            'recepcionAbierta' => RecepcionDeComentarios::estaAbierta(),
            'via' => ViaDeRecepcion::actual(),
            'telefonoValidado' => $this->ventana->telefonoValidado($peticion),
            'telefonoPendiente' => $peticion->session()->get('comentario.telefono'),
        ]);
    }

    /**
     * Manda el código por SMS. El tope de envíos lo cobra el propio OtpService
     * (URVA-5), así que esta ruta no puede saltárselo.
     */
    public function enviarOtp(Request $peticion): RedirectResponse
    {
        if ($rechazo = $this->rechazoSiElSitioNoRecibeComentarios()) {
            return $rechazo;
        }

        $crudo = $peticion->input('telefono');

        if (is_string($crudo)) {
            $peticion->merge(['telefono' => $this->normalizarTelefono($crudo)]);
        }

        $datos = $peticion->validate([
            'telefono' => ['required', 'string', 'regex:/^\+?\d{10,15}$/'],
        ], [
            'telefono.regex' => 'Escribe tu celular a 10 dígitos.',
        ]);

        $this->otp->generar($datos['telefono'], self::PROPOSITO);

        $peticion->session()->put('comentario.telefono', $datos['telefono']);

        return $this->deVueltaAlFormulario()
            ->with('comentario.info', 'Te enviamos un código por SMS. Llega en menos de un minuto.');
    }

    /**
     * Un código correcto abre la Ventana de validación de 30 minutos.
     */
    public function verificarOtp(Request $peticion): RedirectResponse
    {
        if ($rechazo = $this->rechazoSiElSitioNoRecibeComentarios()) {
            return $rechazo;
        }

        $peticion->validate([
            'codigo' => ['required', 'string'],
        ]);

        $telefono = (string) $peticion->session()->get('comentario.telefono', '');

        if ($telefono === '' || ! $this->otp->verificar($telefono, self::PROPOSITO, (string) $peticion->string('codigo'))) {
            return $this->deVueltaAlFormulario()
                ->withErrors(['codigo' => 'El código es incorrecto o expiró.']);
        }

        $peticion->session()->forget('comentario.telefono');

        return $this->deVueltaAlFormulario()
            ->with('comentario.info', 'Tu teléfono quedó validado. Puedes comentar durante los próximos 30 minutos.')
            ->withCookie($this->ventana->abrir($telefono));
    }

    /**
     * Salida para quien escribió mal su celular: suelta el teléfono pendiente y
     * el formulario vuelve a pedirlo, vacío.
     *
     * No borra el OTP que ya se generó — cambiar de número es una decisión de la
     * interfaz, y ese código vence solo a los 5 minutos. Tampoco regala envíos:
     * el tope de URVA-5 se cobra por teléfono *y* por IP, así que rotar números
     * desde la misma máquina sigue topándose con el límite por IP.
     */
    public function cambiarTelefono(Request $peticion): RedirectResponse
    {
        if ($rechazo = $this->rechazoSiElSitioNoRecibeComentarios()) {
            return $rechazo;
        }

        $peticion->session()->forget('comentario.telefono');

        return $this->deVueltaAlFormulario();
    }

    /**
     * El teléfono sale de la cookie de la Ventana de validación, nunca de lo que
     * venga en la petición: sin un OTP completado en este servidor no hay forma
     * de dejar un Comentario, ni saltándose la interfaz.
     */
    public function store(Request $peticion): RedirectResponse
    {
        if ($rechazo = $this->rechazoSiElSitioNoRecibeComentarios()) {
            return $rechazo;
        }

        $telefono = $this->ventana->telefonoValidado($peticion);

        if ($telefono === null) {
            return $this->deVueltaAlFormulario()
                ->withInput($peticion->except('codigo'))
                ->withErrors(['telefono' => 'Tu validación expiró. Valida tu teléfono otra vez para comentar.']);
        }

        $datos = $peticion->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'comentario' => ['required', 'string', 'max:2000'],
            'visibilidad' => ['required', Rule::enum(Visibilidad::class)],
        ], [
            'visibilidad.required' => 'Elige quién puede leer tu comentario.',
        ]);

        $atributos = [
            'telefono' => $telefono,
            'nombre' => $datos['nombre'],
            'comentario' => $datos['comentario'],
            // De dónde vino. Aquí solo hay una página, así que se resuelve en el
            // servidor en vez de confiar en un campo oculto como en nvavista.
            'url' => route('propuesta'),
        ];

        if (Visibilidad::from($datos['visibilidad']) === Visibilidad::Publico) {
            Comentario::crearPublico($atributos);

            return $this->deVueltaAlFormulario()->with(
                'comentario.exito',
                'Recibimos tu comentario. Aparecerá en esta página cuando la Mesa Directiva lo publique.',
            );
        }

        Comentario::crearPrivado($atributos);

        return $this->deVueltaAlFormulario()->with(
            'comentario.exito',
            'Recibimos tu comentario. Solo lo lee la Mesa Directiva: no se publica.',
        );
    }

    /**
     * Las dos razones por las que ninguna de estas cuatro rutas hace nada. Se
     * revisan en este orden porque el interruptor manda sobre la vía: con la
     * recepción cerrada no se admite nada por ningún lado, así que decir por
     * cuál habrían llegado sería contestar otra pregunta.
     *
     * 1. **Recepción de comentarios cerrada:** no se admite nada nuevo — ni el
     *    código, ni la validación, ni el comentario. Lo ya publicado no se toca.
     * 2. **Vía de recepción en `whatsapp`:** el sitio no es por donde se
     *    reciben. Esconder el formulario no basta: son rutas públicas, y
     *    dejarlas vivas permite quemar saldo de Twilio contra un canal que ni
     *    siquiera está activo. Se cierran las cuatro y no solo las dos del OTP:
     *    en esta vía el sitio no recibe comentarios por ninguna de ellas, y el
     *    aviso manda a WhatsApp en vez de dejar un callejón sin salida.
     */
    private function rechazoSiElSitioNoRecibeComentarios(): ?RedirectResponse
    {
        if (! RecepcionDeComentarios::estaAbierta()) {
            return redirect()->route('propuesta')->with('comentario.aviso', self::RECEPCION_CERRADA);
        }

        if (ViaDeRecepcion::actual()->esWhatsApp()) {
            return redirect()->route('propuesta')->with('comentario.aviso', self::RECEPCION_POR_WHATSAPP);
        }

        return null;
    }

    /**
     * El formulario vive al final de una página larga: sin el ancla, quien
     * comenta desde el celular vuelve hasta arriba y no ve el acuse.
     */
    private function deVueltaAlFormulario(): RedirectResponse
    {
        return redirect()->route('propuesta')->withFragment('comentarios');
    }

    /**
     * Deja el número en puros dígitos (con su `+` si lo trae). Sirve para que el
     * mismo celular escrito de dos formas —con guiones, con espacios— sea un
     * solo teléfono para el tope de envíos y para la Cola de moderación.
     */
    private function normalizarTelefono(string $telefono): string
    {
        $telefono = trim($telefono);
        $signo = str_starts_with($telefono, '+') ? '+' : '';

        return $signo.preg_replace('/\D/', '', $telefono);
    }
}
