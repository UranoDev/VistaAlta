<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ViaDeRecepcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La portada del sitio y el orden del menú (URVA-95).
 *
 * Hasta este cambio, la raíz servía la Propuesta y era la primera entrada del
 * menú. Ahora la raíz redirige al Reporte financiero y la Propuesta vive en
 * `/propuesta`, entera. Esta clase cuida las dos mitades de esa mudanza.
 *
 * Casi todo lo demás en la suite pide las páginas por `route('propuesta')`, que
 * sigue resolviendo pase lo que pase con la URL. Por eso aquí se piden las
 * **direcciones literales**: son lo único que un `route()` mal apuntado no
 * puede tapar, y lo único que le importa al Colono que tiene la liga guardada.
 */
class PortadaTest extends TestCase
{
    // La Propuesta lista los Comentarios públicos, así que ya toca la base.
    use RefreshDatabase;

    /**
     * El 301 y no un 302: la dirección vieja anduvo circulando entre los
     * Colonos, y lo que se quiere es que el navegador y los buscadores dejen de
     * pedirla. Un temporal los mandaría a volver a preguntar cada vez.
     */
    public function test_la_raiz_redirige_de_forma_permanente_al_reporte_financiero(): void
    {
        $respuesta = $this->get('/');

        $respuesta->assertStatus(301);
        $respuesta->assertRedirect(route('reporte-financiero'));
    }

    /**
     * La redirección tiene que cambiar la barra del navegador, no servir la
     * misma página en dos direcciones. Sin esto, un alias que devolviera 200 con
     * el Reporte financiero pasaría la prueba de arriba a medias y dejaría a los
     * buscadores repartiendo entre `/` y `/reporte-financiero`.
     */
    public function test_la_raiz_no_sirve_ninguna_pagina_por_su_cuenta(): void
    {
        $this->get('/')->assertDontSee('Formalizar el fraccionamiento', escape: false);

        $this->followingRedirects()
            ->get('/')
            ->assertOk()
            ->assertSee('Reporte financiero', escape: false);
    }

    public function test_la_propuesta_se_sirve_en_su_propia_direccion(): void
    {
        $this->assertSame('/propuesta', parse_url(route('propuesta'), PHP_URL_PATH));

        $this->get('/propuesta')
            ->assertOk()
            ->assertSee('Formalizar el fraccionamiento', escape: false);
    }

    /**
     * La mudanza no le quitó nada a la Propuesta: el formulario de Comentarios
     * con su OTP sigue en la página. Se afirma aquí y no solo en las pruebas de
     * Comentarios porque el riesgo de este cambio era justamente mover la página
     * y dejar la mitad de abajo atrás.
     */
    public function test_la_propuesta_conserva_el_formulario_de_comentarios_en_su_nueva_direccion(): void
    {
        // La Vía de recepción nace en WhatsApp, y en esa vía el sitio no recibe
        // comentarios y retira el formulario a propósito. Lo que se mide aquí es
        // la mudanza, no el interruptor.
        ViaDeRecepcion::usarOtp();

        $this->get('/propuesta')
            ->assertOk()
            ->assertSee('Enviarme el código')
            ->assertSee(route('comentarios.codigo'));
    }

    /**
     * El orden del menú es contenido, no capricho: primero la cuenta del mes,
     * al final lo que se pide. Afirmar que las entradas *están* —lo que ya hace
     * `PaginasPublicasTest`— no protege el orden, que es lo que cambió.
     *
     * Convivencia entró tercera después (URVA-97), entre Actividades y
     * Vigilancia: se lee, no se pide.
     *
     * Y en la quinta posición Administración tomó el lugar de Propuesta
     * (URVA-99), que salió del menú sin salir del sitio.
     */
    public function test_el_menu_lleva_el_orden_nuevo(): void
    {
        $contenido = $this->get('/propuesta')->getContent();

        $esperado = ['reporte-financiero', 'actividades', 'convivencia', 'vigilancia', 'administracion', 'demanda'];

        $posiciones = [];

        foreach ($esperado as $ruta) {
            $posicion = strpos($contenido, '<a href="'.route($ruta).'"');

            $this->assertNotFalse($posicion, "El menú debe enlazar a «{$ruta}».");

            $posiciones[$ruta] = $posicion;
        }

        $ordenado = $posiciones;
        asort($ordenado);

        $this->assertSame(
            array_keys($posiciones),
            array_keys($ordenado),
            'El menú debe ir: Reporte financiero, Actividades, Convivencia, Vigilancia, Propuesta, Demanda.',
        );
    }

    /**
     * El logo es el enlace a la portada, y la portada ya no es la Propuesta.
     * Apunta al Reporte financiero directo y no a `/`, para no gastar un salto
     * de redirección en el enlace que más se toca del sitio.
     */
    public function test_el_logo_lleva_al_reporte_financiero_y_no_a_la_raiz(): void
    {
        $contenido = $this->get('/propuesta')->getContent();

        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('reporte-financiero'), '/').'"[^>]*>\s*<span[^>]*>\s*Vista Alta\s*</',
            $contenido,
            'El logo debe enlazar al Reporte financiero.',
        );
    }
}
