<?php

declare(strict_types=1);

namespace Tests\Feature\Convivencia;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La página de un post: `/convivencia/{slug}`.
 *
 * Aquí se piden las **direcciones literales** y no `route()`, por lo mismo que
 * en `PortadaTest`: lo que le importa al Colono es la liga que le pegaron en el
 * grupo de vecinos, y un `route()` bien apuntado tapa el día que la dirección
 * cambie de forma.
 */
class PaginaDelPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_post_se_sirve_en_su_propia_direccion_y_sin_autenticacion(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create(['titulo' => 'Manejo de la basura']);

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertOk()
            ->assertSee('Manejo de la basura');
    }

    public function test_la_direccion_del_post_sale_de_su_slug(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create();

        $this->assertSame('/convivencia/manejo-de-la-basura', parse_url($post->urlPublica(), PHP_URL_PATH));
    }

    /**
     * El contenido se pinta como HTML de verdad: eso es lo que separa un post de
     * una Actividad, que se lee como un renglón de texto plano.
     */
    public function test_el_contenido_se_pinta_con_el_formato_del_markdown(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create([
            'contenido' => "## Los días de recolección\n\nLa basura **se saca** los martes.\n\n- Orgánica\n- Inorgánica\n",
        ]);

        $respuesta = $this->get('/convivencia/manejo-de-la-basura')->assertOk();

        $respuesta->assertSee('<h2>Los días de recolección</h2>', escape: false);
        $respuesta->assertSee('<strong>se saca</strong>', escape: false);
        $respuesta->assertSee('<li>Orgánica</li>', escape: false);
    }

    /**
     * La página pinta el contenido con `{!! !!}`, así que lo que hay del otro
     * lado tiene que estar saneado. `MarkdownTest` cubre la clase; esto cubre
     * que la página la esté usando de verdad y no imprimiendo el campo crudo.
     */
    public function test_la_pagina_no_saca_vivo_el_html_capturado_en_el_panel(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create([
            'contenido' => "Texto normal.\n\n<script>alert('xss')</script>",
        ]);

        $respuesta = $this->get('/convivencia/manejo-de-la-basura')->assertOk();

        $respuesta->assertSee('Texto normal.');
        $respuesta->assertDontSee('<script>alert', escape: false);
    }

    /**
     * Las imágenes van dentro del Markdown, subidas desde el editor del panel.
     * Sin esto, la mitad del contenido de un post —fotos del acceso, del
     * contenedor— no llegaría a la página.
     */
    public function test_la_imagen_del_post_llega_a_la_pagina(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create([
            'contenido' => '![Los contenedores](/storage/convivencia/basura.png)',
        ]);

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertSee('src="/storage/convivencia/basura.png"', escape: false);
    }

    /**
     * El slug es editable a propósito y el costo está asumido (URVA-96): un
     * enlace ya compartido queda en 404 si la dirección cambia. Lo que no puede
     * pasar es que reviente con un error de servidor.
     */
    public function test_una_direccion_que_no_existe_da_404(): void
    {
        $this->get('/convivencia/esto-no-existe')->assertNotFound();
    }

    public function test_la_direccion_vieja_queda_en_404_al_cambiar_el_slug(): void
    {
        $post = Post::factory()->conSlug('direccion-vieja')->create();

        $post->update(['slug' => 'direccion-nueva']);

        $this->get('/convivencia/direccion-vieja')->assertNotFound();
        $this->get('/convivencia/direccion-nueva')->assertOk();
    }

    /**
     * El post declara su propia dirección canónica. No es el empate del Reporte
     * financiero —aquí cada post se sirve en un solo lugar—, pero la liga se
     * pega con parámetros de campaña pegados atrás y sin esto los buscadores
     * indexan cada variante como si fuera otra página.
     */
    public function test_el_post_declara_su_direccion_canonica(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create();

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertSee('<link rel="canonical" href="'.$post->urlPublica().'">', escape: false);
    }

    /**
     * El renglón que WhatsApp pinta debajo del título cuando alguien pega la
     * liga sale del propio contenido del post, no del texto general del sitio.
     * Un post compartido con el resumen de la rendición de cuentas no dice de
     * qué se trata.
     */
    public function test_la_tarjeta_para_compartir_resume_el_post_y_no_el_sitio(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create([
            'titulo' => 'Manejo de la basura',
            'contenido' => 'La basura se saca los martes y los viernes, antes de las siete.',
        ]);

        $respuesta = $this->get('/convivencia/manejo-de-la-basura')->assertOk();

        $respuesta->assertSee('content="La basura se saca los martes y los viernes, antes de las siete."', escape: false);
        // La descripción de fábrica del layout, que es la que hay que desplazar.
        // La frase se toma entera: «Rendición de cuentas de la Mesa Directiva»
        // a secas también está en el pie de todas las páginas, y afirmar sobre
        // ella mediría el pie en vez de la etiqueta.
        $respuesta->assertDontSee('Rendición de cuentas de la Mesa Directiva de Vista Alta: lo que se hizo', escape: false);
    }

    /**
     * El menú tiene que seguir señalando de qué sección es la página que se está
     * leyendo. Sin el comodín en `routeIs`, dentro de un post ninguna entrada
     * queda marcada y el lector pierde de vista dónde está parado.
     */
    public function test_dentro_de_un_post_el_menu_sigue_marcando_convivencia(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create();

        $contenido = $this->get('/convivencia/manejo-de-la-basura')->getContent();

        $this->assertMatchesRegularExpression(
            '/<a href="'.preg_quote(route('convivencia'), '/').'"\s+aria-current="page"/',
            $contenido,
            'La entrada de Convivencia debe quedar marcada como activa dentro de un post.',
        );
    }

    public function test_el_post_ofrece_la_vuelta_al_indice(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create();

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertSee('Ver todas las publicaciones de Convivencia');
    }
}
