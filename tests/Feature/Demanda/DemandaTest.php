<?php

declare(strict_types=1);

namespace Tests\Feature\Demanda;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * La página que pide los comprobantes de depósito.
 *
 * Lo que se protege aquí es que el número salga de la configuración: si
 * volviera al blade, la página se quedaría en la cifra vieja el día que
 * lleguen más comprobantes y estaría mintiendo.
 */
class DemandaTest extends TestCase
{
    // La página en sí no toca la base, pero la comparación del Rojo Sello
    // visita la Propuesta, que lista los Comentarios públicos.
    use RefreshDatabase;

    public function test_la_pagina_responde_sin_autenticacion(): void
    {
        $respuesta = $this->get(route('demanda'));

        $respuesta->assertOk();
        $respuesta->assertSee('Faltan tus comprobantes', escape: false);
    }

    public function test_muestra_el_numero_que_trae_la_configuracion(): void
    {
        config(['contenido.comprobantes_recibidos' => 17]);

        $this->get(route('demanda'))->assertSee('17');
    }

    public function test_el_correo_sale_de_la_configuracion_y_va_como_mailto(): void
    {
        config(['contenido.correo_comprobantes' => 'buzon@ejemplo.test']);

        $this->get(route('demanda'))
            ->assertSee('mailto:buzon@ejemplo.test', escape: false)
            ->assertSee('buzon@ejemplo.test');
    }

    /**
     * Los dos buzones están separados a propósito: el de los comprobantes se
     * puede apagar el día que se llene de spam, y el institucional no —lo
     * citan las páginas legales como contacto para derechos ARCO y tiene que
     * seguir contestando—. Esta prueba es lo que evita que alguien vuelva a
     * unificar las dos llaves «por limpieza» y deshaga la separación sin
     * darse cuenta.
     */
    public function test_no_muestra_el_correo_institucional(): void
    {
        config([
            'contenido.correo_comprobantes' => 'comprobantes@ejemplo.test',
            'contenido.correo_contacto' => 'institucional@ejemplo.test',
        ]);

        $this->get(route('demanda'))
            ->assertSee('comprobantes@ejemplo.test')
            ->assertDontSee('institucional@ejemplo.test');
    }

    public function test_el_enlace_aparece_en_la_navegacion_y_se_marca_al_estar_en_ella(): void
    {
        $this->get(route('demanda'))
            ->assertSee(route('demanda'))
            ->assertSee('aria-current="page"', escape: false);
    }

    /**
     * La página se llama «Demanda» en los tres lugares donde se identifica:
     * navegación, `<title>` y rótulo de la sección. Antes cada uno decía algo
     * distinto y nada lo protegía, así que el nombre podía volver a divergir
     * sin que tronara ninguna prueba.
     *
     * El titular no entra aquí: «Faltan tus comprobantes» es el que hace el
     * trabajo y se queda.
     */
    public function test_la_navegacion_nombra_la_pagina_demanda(): void
    {
        $contenido = $this->get(route('propuesta'))->getContent();

        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('demanda'), '/').'"[^>]*>\s*Demanda\s*</',
            $contenido,
            'La navegación debe rotular la página como «Demanda».',
        );
    }

    public function test_el_titulo_y_el_rotulo_dicen_demanda_y_el_titular_no_cambia(): void
    {
        $respuesta = $this->get(route('demanda'));

        $respuesta->assertSee('<title>Demanda · '.config('app.name').'</title>', escape: false);
        $respuesta->assertSee('Faltan tus comprobantes', escape: false);

        // El rótulo es el `<p>` en mono que encabeza la sección.
        $this->assertMatchesRegularExpression(
            '/<p class="cifra[^"]*">\s*Demanda\s*<\/p>/',
            $respuesta->getContent(),
            'El rótulo de la sección debe decir «Demanda».',
        );
    }

    /**
     * La frase que dice qué se está pidiendo quedó fuera del `trans_choice()`
     * para poder resaltarla, así que hay que comprobar que la oración sigue
     * armándose bien en las tres formas plurales.
     */
    #[DataProvider('conteosDeComprobantes')]
    public function test_la_oracion_del_numero_concuerda_en_las_tres_formas(int $recibidos, string $esperado): void
    {
        config(['contenido.comprobantes_recibidos' => $recibidos]);

        $this->get(route('demanda'))
            ->assertSee($esperado)
            ->assertSee('comprobante de depósito a la administración pasada');
    }

    public static function conteosDeComprobantes(): array
    {
        return [
            'ninguno' => [0, 'propietarios de todo el fraccionamiento han entregado a la Mesa Directiva su'],
            'uno' => [1, 'propietario de todo el fraccionamiento ha entregado a la Mesa Directiva su'],
            'varios' => [7, 'propietarios de todo el fraccionamiento han entregado a la Mesa Directiva su'],
        ];
    }

    /**
     * Esta es la única página que pide algo en vez de rendir cuentas, y
     * cualquier salida es una fuga. La regla no es «cero etiquetas `<a>`»: el
     * `mailto:` es la acción entera de la página y se queda. Lo que no puede
     * haber es un enlace que devuelva al visitante a otra parte del sitio.
     *
     * Se mira solo el `<main>`: el encabezado y el pie traen su navegación en
     * todas las páginas, y no es lo que este criterio gobierna.
     */
    public function test_el_contenido_no_lleva_enlaces_de_navegacion_interna(): void
    {
        $principal = $this->contenidoPrincipal($this->get(route('demanda'))->getContent());

        preg_match_all('/<a\s[^>]*href="([^"]*)"/i', $principal, $coincidencias);

        $this->assertNotEmpty($coincidencias[1], 'Se esperaba al menos el enlace del correo.');

        foreach ($coincidencias[1] as $destino) {
            $this->assertTrue(
                str_starts_with($destino, 'mailto:'),
                "La página no debe llevar enlaces de navegación interna; se encontró «{$destino}».",
            );
        }
    }

    private function contenidoPrincipal(string $html): string
    {
        $inicio = strpos($html, '<main');
        $fin = strpos($html, '</main>');

        $this->assertNotFalse($inicio);
        $this->assertNotFalse($fin);

        return substr($html, $inicio, $fin - $inicio);
    }

    /**
     * El Rojo Sello está reservado a alertas y urgencia. Esta página es la
     * excepción para la que se guardó; si empieza a aparecer en las otras,
     * deja de significar algo y ésta pierde su fuerza.
     */
    public function test_el_rojo_sello_aparece_aqui_y_no_en_las_otras_paginas(): void
    {
        $this->get(route('demanda'))->assertSee('text-sello', escape: false);

        foreach (['propuesta', 'actividades', 'reporte-financiero'] as $ruta) {
            $this->get(route($ruta))->assertDontSee('-sello', escape: false);
        }
    }
}
