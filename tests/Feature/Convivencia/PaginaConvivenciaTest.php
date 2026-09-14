<?php

declare(strict_types=1);

namespace Tests\Feature\Convivencia;

use App\Models\IntroDeConvivencia;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El índice de `/convivencia`: la introducción arriba y las publicaciones
 * debajo, más reciente primero.
 */
class PaginaConvivenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_indice_se_sirve_sin_autenticacion(): void
    {
        $this->get('/convivencia')
            ->assertOk()
            ->assertSee('Convivencia');
    }

    public function test_el_indice_lista_los_posts_con_su_enlace(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create(['titulo' => 'Manejo de la basura']);

        $this->get('/convivencia')
            ->assertOk()
            ->assertSee('Manejo de la basura')
            ->assertSee($post->urlPublica());
    }

    /**
     * El índice lee la tabla en cada petición. Es lo que hace que cambiar un
     * slug desde el panel no deje al índice enlazando a la dirección vieja
     * (URVA-96).
     */
    public function test_el_indice_enlaza_a_la_direccion_vigente_y_no_a_la_anterior(): void
    {
        $post = Post::factory()->conSlug('direccion-vieja')->create();

        $post->update(['slug' => 'direccion-nueva']);

        $this->get('/convivencia')
            ->assertSee('/convivencia/direccion-nueva')
            ->assertDontSee('/convivencia/direccion-vieja');
    }

    /**
     * El orden es de lo más reciente hacia atrás. Se comprueba por posición y no
     * por presencia: una prueba que solo mire que los tres estén pasa igual con
     * el orden invertido, que es justo el defecto que importa aquí.
     */
    public function test_los_posts_van_del_mas_reciente_al_mas_viejo(): void
    {
        Post::factory()->publicadoEn('2026-05-10')->create(['titulo' => 'El más viejo']);
        Post::factory()->publicadoEn('2026-08-01')->create(['titulo' => 'El más reciente']);
        Post::factory()->publicadoEn('2026-06-20')->create(['titulo' => 'El de en medio']);

        $contenido = $this->get('/convivencia')->getContent();

        $this->assertLessThan(
            strpos($contenido, 'El de en medio'),
            strpos($contenido, 'El más reciente'),
        );

        $this->assertLessThan(
            strpos($contenido, 'El más viejo'),
            strpos($contenido, 'El de en medio'),
        );
    }

    /**
     * El índice muestra el arranque del post, no el post entero: para eso está
     * su propia página.
     */
    public function test_el_indice_muestra_el_arranque_del_post_y_no_el_texto_completo(): void
    {
        Post::factory()->create([
            'titulo' => 'Manejo de la basura',
            'contenido' => "La basura se saca los martes y los viernes.\n\n".str_repeat('Un párrafo que sigue mucho más abajo. ', 20).'ESTO VA AL FINAL',
        ]);

        $this->get('/convivencia')
            ->assertSee('La basura se saca los martes')
            ->assertDontSee('ESTO VA AL FINAL');
    }

    public function test_sin_posts_el_indice_lo_dice_en_vez_de_quedarse_en_blanco(): void
    {
        $this->get('/convivencia')
            ->assertOk()
            ->assertSee('Todavía no hay publicaciones');
    }

    /**
     * La introducción se captura desde el panel y puede no existir. Vacía es un
     * estado legítimo: el índice arranca directo con las publicaciones, sin
     * texto de relleno.
     */
    public function test_sin_introduccion_capturada_el_indice_arranca_con_las_publicaciones(): void
    {
        $this->get('/convivencia')
            ->assertOk()
            ->assertSee('Publicaciones');

        $this->assertNull(IntroDeConvivencia::texto());
    }

    public function test_la_introduccion_capturada_se_lee_arriba_de_las_publicaciones(): void
    {
        IntroDeConvivencia::cambiar('Aquí publicamos los acuerdos de convivencia del fraccionamiento.');

        Post::factory()->create(['titulo' => 'Manejo de la basura']);

        $contenido = $this->get('/convivencia')->assertOk()->getContent();

        $this->assertStringContainsString('Aquí publicamos los acuerdos de convivencia', $contenido);

        $this->assertLessThan(
            strpos($contenido, 'Manejo de la basura'),
            strpos($contenido, 'Aquí publicamos los acuerdos'),
        );
    }

    /**
     * La introducción es texto plano con ligas internas, el mismo trato que la
     * Bitácora: lo único que habilita es mandar al lector a otra página del
     * sitio con una frase.
     */
    public function test_la_introduccion_admite_una_liga_interna_y_nada_de_html(): void
    {
        IntroDeConvivencia::cambiar('Lo que se hizo está en [Actividades](/actividades). <script>alert(1)</script>');

        $contenido = $this->get('/convivencia')->getContent();

        $this->assertStringContainsString('<a href="/actividades"', $contenido);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $contenido);
    }

    /**
     * Convivencia entró al menú en tercer lugar, entre Actividades y Vigilancia
     * (URVA-96). El orden es contenido: primero lo que la Mesa Directiva cuenta,
     * al final lo que pide.
     *
     * La quinta dejó de ser Propuesta y es Administración (URVA-99): lo que se
     * sometía a consideración ya está en trámite, y por dónde va se rinde ahí.
     */
    public function test_convivencia_va_tercera_en_el_menu(): void
    {
        $contenido = $this->get('/convivencia')->getContent();

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
}
