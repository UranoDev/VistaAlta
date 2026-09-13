<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\IntroDeConvivencia;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * El CRUD de Convivencia en el panel de la Mesa Directiva, más la introducción
 * del índice, que vive en el encabezado de esta misma pantalla.
 *
 * Lo que se captura aquí sale publicado al guardarlo: no hay borradores ni
 * moderación, igual que en Actividades.
 */
class ConvivenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_el_panel_lista_los_posts(): void
    {
        $post = Post::factory()->create(['titulo' => 'Manejo de la basura']);

        Livewire::test(ListPosts::class)
            ->assertCanSeeTableRecords([$post])
            ->assertSee('Manejo de la basura');
    }

    public function test_dar_de_alta_un_post_lo_publica_en_el_sitio(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'titulo' => 'Manejo de la basura',
                'slug' => 'manejo-de-la-basura',
                'publicado_en' => '2026-08-15',
                'contenido' => "## Días de recolección\n\nLa basura **se saca** los martes.",
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', ['slug' => 'manejo-de-la-basura']);

        auth()->logout();

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertOk()
            ->assertSee('<h2>Días de recolección</h2>', escape: false);
    }

    /**
     * El slug se sugiere del título al capturar. Es una comodidad, no un
     * candado: el campo queda editable, y la prueba de abajo comprueba que
     * escribir uno propio manda sobre la sugerencia.
     */
    public function test_el_slug_se_sugiere_del_titulo_al_crear(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm(['titulo' => 'Manejo de la Basura'])
            ->assertFormSet(['slug' => 'manejo-de-la-basura']);
    }

    /**
     * En un post ya publicado el título no arrastra al slug: cambiaría la
     * dirección sin avisar y dejaría en 404 el enlace que ya anda circulando.
     */
    public function test_al_editar_el_titulo_la_direccion_no_se_mueve_sola(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create(['titulo' => 'Manejo de la basura']);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->fillForm(['titulo' => 'Manejo de la basura y del reciclaje'])
            ->assertFormSet(['slug' => 'manejo-de-la-basura'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('manejo-de-la-basura', $post->refresh()->slug);
    }

    public function test_la_direccion_se_puede_cambiar_a_mano(): void
    {
        $post = Post::factory()->conSlug('direccion-vieja')->create();

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->fillForm(['slug' => 'direccion-nueva'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('direccion-nueva', $post->refresh()->slug);
    }

    public function test_los_cuatro_campos_son_obligatorios(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm(['titulo' => null, 'slug' => null, 'publicado_en' => null, 'contenido' => null])
            ->call('create')
            ->assertHasFormErrors([
                'titulo' => 'required',
                'slug' => 'required',
                'publicado_en' => 'required',
                'contenido' => 'required',
            ]);
    }

    /**
     * Dos posts con la misma dirección dejarían a la ruta sirviendo cualquiera
     * de los dos.
     */
    public function test_no_se_puede_repetir_la_direccion_de_otro_post(): void
    {
        Post::factory()->conSlug('manejo-de-la-basura')->create();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'titulo' => 'Otro post',
                'slug' => 'manejo-de-la-basura',
                'publicado_en' => '2026-08-15',
                'contenido' => 'Texto.',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    /**
     * El patrón que valida el panel es el mismo que acepta la ruta
     * (`routes/web.php`). Si el panel dejara pasar un slug con mayúsculas o
     * espacios, el post quedaría capturado pero inalcanzable.
     */
    public function test_una_direccion_que_la_ruta_no_aceptaria_se_rechaza(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'titulo' => 'Manejo de la basura',
                'slug' => 'Manejo De La Basura',
                'publicado_en' => '2026-08-15',
                'contenido' => 'Texto.',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_editar_un_post_cambia_lo_que_lee_el_colono(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create([
            'contenido' => 'Redacción con un error.',
        ]);

        Livewire::test(EditPost::class, ['record' => $post->getKey()])
            ->fillForm(['contenido' => 'Redacción corregida.'])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertSee('Redacción corregida.')
            ->assertDontSee('Redacción con un error.');
    }

    /**
     * La introducción del índice se captura en el encabezado de esta pantalla, y
     * lo capturado se lee de inmediato en la página pública.
     */
    public function test_la_introduccion_se_captura_desde_el_encabezado(): void
    {
        Livewire::test(ListPosts::class)
            ->fillForm(['intro' => 'Aquí publicamos los acuerdos de convivencia.']);

        $this->assertSame('Aquí publicamos los acuerdos de convivencia.', IntroDeConvivencia::texto());

        auth()->logout();

        $this->get('/convivencia')->assertSee('Aquí publicamos los acuerdos de convivencia.');
    }

    /**
     * Vaciarla es una acción legítima —el índice arranca directo con las
     * publicaciones— y no se puede confundir con «no se guardó».
     */
    public function test_vaciar_la_introduccion_la_quita_de_la_pagina(): void
    {
        IntroDeConvivencia::cambiar('Un texto que va a salir de la página.');

        Livewire::test(ListPosts::class)
            ->assertFormSet(['intro' => 'Un texto que va a salir de la página.'])
            ->fillForm(['intro' => '']);

        $this->assertNull(IntroDeConvivencia::texto());

        auth()->logout();

        $this->get('/convivencia')
            ->assertOk()
            ->assertDontSee('Un texto que va a salir de la página.');
    }

    public function test_borrar_un_post_lo_saca_del_indice(): void
    {
        $post = Post::factory()->conSlug('manejo-de-la-basura')->create(['titulo' => 'Manejo de la basura']);

        $post->delete();

        auth()->logout();

        $this->get('/convivencia')->assertDontSee('Manejo de la basura');
        $this->get('/convivencia/manejo-de-la-basura')->assertNotFound();
    }
}
