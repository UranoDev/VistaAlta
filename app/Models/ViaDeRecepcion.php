<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Via;
use Illuminate\Database\Eloquent\Model;

/**
 * El selector de canal que la Mesa Directiva mueve desde su panel: si los
 * Comentarios se escriben en el sitio validando el celular por SMS (`otp`) o si
 * se reciben por WhatsApp (`whatsapp`).
 *
 * **No es el interruptor de la Recepción de comentarios.** Aquél es un apagador
 * —¿se admiten comentarios nuevos, sí o no?— y manda sobre éste: con la
 * recepción cerrada la vía da igual, porque no se admite nada por ninguna. Éste
 * solo decide por dónde llegan cuando sí se admiten.
 *
 * Nace en `whatsapp` (URVA-26): en producción el SMS de Twilio no se entrega
 * —error 30008, las operadoras filtran el tráfico A2P de un long code sin
 * registro— y una página que promete un código que nunca llega es peor que no
 * ofrecer el canal. El modo `otp` es el bueno y sigue entero: cuando Twilio
 * destrabe el registro, la Mesa Directiva vuelve a él desde el panel, sin
 * despliegue. Ése es justo el punto de que esto sea un interruptor.
 *
 * Es una tabla de un solo renglón. Mientras nadie la haya tocado no hay renglón,
 * y la lectura responde con los valores de fábrica: así el sitio arranca sin
 * depender de que se haya sembrado nada.
 */
class ViaDeRecepcion extends Model
{
    /**
     * El celular de la Mesa Directiva mientras nadie capture otro en el panel.
     * Es solo el arranque: quien manda es la columna.
     */
    public const NUMERO_DE_FABRICA = '525531269267';

    protected $table = 'via_de_recepcion';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'via' => Via::class,
        ];
    }

    /**
     * El renglón único, ya exista o no. Lectura barata y sin efectos: una página
     * pública no debería escribir en la base solo por preguntar por dónde se
     * reciben los comentarios.
     *
     * Ni `via` ni `numero_whatsapp` son asignables en masa: se mueven por los
     * métodos de abajo, nunca con datos de una petición.
     */
    public static function actual(): self
    {
        $seleccion = static::query()->first();

        if ($seleccion === null) {
            $seleccion = new self;
            $seleccion->via = Via::WhatsApp;
        }

        return $seleccion;
    }

    public static function usarOtp(): void
    {
        static::mover(Via::Otp);
    }

    public static function usarWhatsApp(): void
    {
        static::mover(Via::WhatsApp);
    }

    /**
     * Guarda el celular en puros dígitos, igual que se guardan los teléfonos de
     * los Comentarios: el mismo número escrito con espacios, guiones o paréntesis
     * tiene que producir el mismo enlace.
     */
    public static function cambiarNumeroDeWhatsApp(string $numero): void
    {
        $seleccion = static::actual();
        $seleccion->numero_whatsapp = self::soloDigitos($numero);
        $seleccion->save();
    }

    public function esOtp(): bool
    {
        return $this->via === Via::Otp;
    }

    public function esWhatsApp(): bool
    {
        return $this->via === Via::WhatsApp;
    }

    /**
     * El número tal como lo necesita `wa.me`: dígitos con lada de país y sin
     * signos.
     */
    public function numeroDeWhatsApp(): string
    {
        $numero = self::soloDigitos((string) $this->numero_whatsapp);

        return $numero !== '' ? $numero : self::NUMERO_DE_FABRICA;
    }

    /**
     * El mismo número, escrito como se lee: `+52 55 3126 9267`. La página lo
     * muestra junto al enlace — quien abre el sitio desde una computadora sin
     * WhatsApp necesita poder anotarlo.
     */
    public function numeroLegible(): string
    {
        $digitos = $this->numeroDeWhatsApp();

        // Solo se despieza el formato mexicano (52 + 10 dígitos), que es el caso
        // real. Cualquier otro se muestra tal cual antes que partirlo mal.
        if (preg_match('/^52(\d{2})(\d{4})(\d{4})$/', $digitos, $partes) === 1) {
            return "+52 {$partes[1]} {$partes[2]} {$partes[3]}";
        }

        return '+'.$digitos;
    }

    /**
     * El enlace que abre la conversación, con el mensaje ya escrito.
     *
     * `wa.me` resuelve igual en celular y en escritorio, así que es un solo
     * enlace para las dos. El `?text=` no es cortesía: en esta vía el comentario
     * lo captura la Mesa Directiva, y es lo único que hace que la intención de
     * visibilidad venga por escrito del propio autor en vez de interpretarse.
     */
    public function enlaceDeWhatsApp(): string
    {
        $mensaje = (string) config('contenido.whatsapp_mensaje', '');

        return 'https://wa.me/'.$this->numeroDeWhatsApp().'?text='.rawurlencode($mensaje);
    }

    private static function mover(Via $via): void
    {
        $seleccion = static::actual();
        $seleccion->via = $via;
        $seleccion->save();
    }

    private static function soloDigitos(string $numero): string
    {
        return (string) preg_replace('/\D/', '', $numero);
    }
}
