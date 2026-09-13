<?php

declare(strict_types=1);

namespace Tests\Feature\Contenido;

use App\Models\Post;
use App\Models\ReporteFinanciero;
use Database\Seeders\ContenidoInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La siembra del contenido con el que el sitio sale al aire.
 *
 * Lo que se protege aquí es que el archivo de contenido se pueda pegar dos
 * veces sin ensuciar la base, y que un renglón a medias no llegue a la página
 * pública.
 */
class ContenidoInicialTest extends TestCase
{
    use RefreshDatabase;

    public function test_siembra_las_actividades_del_archivo(): void
    {
        $this->sembrar([
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => 'Se repararon las luminarias.'],
                ['fecha' => '2026-06-02', 'descripcion' => 'Se renovó el contrato de vigilancia.'],
            ],
        ]);

        $this->assertDatabaseCount('actividades', 2);
        $this->assertDatabaseHas('actividades', ['descripcion' => 'Se repararon las luminarias.']);

        $this->get(route('actividades'))->assertSee('Se renovó el contrato de vigilancia.');
    }

    /**
     * El archivo se siembra en un despliegue y se vuelve a sembrar en el
     * siguiente. Si eso duplicara la bitácora, la Asamblea leería dos veces
     * cada cosa que se hizo.
     */
    public function test_sembrar_dos_veces_no_duplica_actividades(): void
    {
        $contenido = [
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => 'Se repararon las luminarias.'],
            ],
        ];

        $this->sembrar($contenido);
        $this->sembrar($contenido);

        $this->assertDatabaseCount('actividades', 1);
    }

    public function test_una_actividad_sin_fecha_o_sin_texto_no_se_siembra(): void
    {
        $this->sembrar([
            'actividades' => [
                ['fecha' => '2026-05-14', 'descripcion' => ''],
                ['fecha' => '', 'descripcion' => 'Sin fecha.'],
                ['fecha' => '2026-05-20', 'descripcion' => 'La única completa.'],
            ],
        ]);

        $this->assertDatabaseCount('actividades', 1);
        $this->assertDatabaseHas('actividades', ['descripcion' => 'La única completa.']);
    }

    public function test_siembra_los_pendientes_en_el_orden_del_archivo(): void
    {
        $this->sembrar([
            'pendientes' => [
                ['titulo' => 'Constituir la Asociación Civil', 'detalle' => 'De ahí sale la cuenta a nombre del fraccionamiento.'],
                ['titulo' => 'Alumbrado público al 100%', 'detalle' => 'Reponer lo que está apagado y mantenerlo así.'],
            ],
        ]);

        $this->assertDatabaseCount('pendientes', 2);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'Constituir la Asociación Civil', 'orden' => 0]);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'Alumbrado público al 100%', 'orden' => 1]);

        $this->get(route('actividades'))->assertSeeInOrder([
            'Constituir la Asociación Civil',
            'Alumbrado público al 100%',
        ]);
    }

    public function test_sembrar_dos_veces_no_duplica_pendientes(): void
    {
        $contenido = [
            'pendientes' => [
                ['titulo' => 'Coladera repuesta', 'detalle' => 'Le corresponde a la Fraccionadora.'],
            ],
        ];

        $this->sembrar($contenido);
        $this->sembrar($contenido);

        $this->assertDatabaseCount('pendientes', 1);
    }

    public function test_un_pendiente_sin_titulo_o_sin_detalle_no_se_siembra(): void
    {
        $this->sembrar([
            'pendientes' => [
                ['titulo' => 'Sin detalle', 'detalle' => ''],
                ['titulo' => '', 'detalle' => 'Sin título.'],
                ['titulo' => 'El único completo', 'detalle' => 'Con las dos partes.'],
            ],
        ]);

        $this->assertDatabaseCount('pendientes', 1);
        $this->assertDatabaseHas('pendientes', ['titulo' => 'El único completo']);
    }

    public function test_siembra_el_reporte_financiero_con_su_resumen_y_su_hoja(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
                'mes' => '2026-05',
                'hoja_url' => 'https://docs.google.com/spreadsheets/d/abc123/edit',
                'cifras' => [
                    ['concepto' => 'Cuotas recibidas', 'monto' => 48250.5],
                    ['concepto' => 'Saldo final', 'monto' => 12100, 'destacada' => true],
                ],
            ],
        ]);

        $reporte = ReporteFinanciero::actual();

        $this->assertSame('2026-05', $reporte->mesEnUrl());
        $this->assertSame('Mayo de 2026', $reporte->periodo);
        $this->assertSame('https://docs.google.com/spreadsheets/d/abc123/edit', $reporte->hoja_url);
        $this->assertCount(2, $reporte->resumen());
        $this->assertTrue($reporte->resumen()->last()->destacada);

        $this->get(route('reporte-financiero'))
            ->assertSee('Cuotas recibidas')
            ->assertSee('$48,250.50');
    }

    /**
     * Un Reporte se identifica por el mes que cubre: sembrar el mismo mes otra
     * vez lo corrige, nunca deja dos junios conviviendo sin que se sepa cuál
     * vale.
     */
    public function test_sembrar_dos_veces_el_mismo_mes_lo_corrige_en_vez_de_duplicarlo(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cifra con un error', 'monto' => 1]],
        ]]);
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cifra corregida', 'monto' => 2]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 1);
        $this->assertSame('Cifra corregida', ReporteFinanciero::actual()->resumen()->first()->concepto);
    }

    /**
     * El contrato inverso, y el que hace posible el histórico (docs/adr/0005):
     * sembrar otro mes lo agrega en vez de pisar al anterior.
     */
    public function test_sembrar_otro_mes_lo_agrega_al_historico(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-06',
            'cifras' => [['concepto' => 'Cuotas de junio', 'monto' => 1]],
        ]]);
        $this->sembrar(['reporte_financiero' => [
            'mes' => '2026-07',
            'cifras' => [['concepto' => 'Cuotas de julio', 'monto' => 2]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 2);
        $this->assertSame('2026-07', ReporteFinanciero::actual()->mesEnUrl());

        $this->get(route('reporte-financiero.mes', ['mes' => '2026-06']))
            ->assertOk()
            ->assertSee('Cuotas de junio');
    }

    /**
     * Sin mes no hay dónde publicarlo: de él salen la dirección del reporte y
     * su lugar en el histórico. Se salta y se avisa, igual que una Actividad
     * sin fecha.
     */
    public function test_un_reporte_sin_mes_o_con_un_mes_que_no_se_entiende_no_se_siembra(): void
    {
        $this->sembrar(['reporte_financiero' => [
            'cifras' => [['concepto' => 'Sin mes', 'monto' => 1]],
        ]]);

        $this->sembrar(['reporte_financiero' => [
            'mes' => 'Junio',
            'cifras' => [['concepto' => 'Con un mes que no se entiende', 'monto' => 1]],
        ]]);

        $this->assertDatabaseCount('reporte_financiero', 0);
    }

    public function test_una_cifra_sin_concepto_o_sin_monto_no_se_siembra(): void
    {
        $this->sembrar([
            'reporte_financiero' => [
                'mes' => '2026-06',
                'cifras' => [
                    ['concepto' => 'Sin monto'],
                    ['concepto' => '', 'monto' => 100],
                    ['concepto' => 'La única completa', 'monto' => 100],
                ],
            ],
        ]);

        $this->assertCount(1, ReporteFinanciero::actual()->resumen());
    }

    /**
     * Mientras la Mesa Directiva no mande el material, sembrar no debe dejar
     * al sitio publicando un reporte en blanco ni una bitácora vacía: la
     * página ya sabe decir que ese contenido todavía no se publica.
     */
    public function test_con_el_archivo_vacio_no_escribe_nada(): void
    {
        $this->sembrar([
            'actividades' => [],
            'pendientes' => [],
            'posts' => [],
            'reporte_financiero' => ['mes' => null, 'hoja_url' => null, 'cifras' => []],
        ]);

        $this->assertDatabaseCount('actividades', 0);
        $this->assertDatabaseCount('pendientes', 0);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('reporte_financiero', 0);
    }

    /**
     * El archivo que se despliega tiene que seguir siendo cargable y con la
     * forma que el seeder espera, por más que lo edite alguien que no
     * escribe PHP a diario.
     */
    public function test_el_archivo_de_contenido_tiene_la_forma_esperada(): void
    {
        $contenido = require database_path('seeders/contenido/contenido-inicial.php');

        $this->assertIsArray($contenido['actividades']);
        $this->assertIsArray($contenido['pendientes']);
        $this->assertIsArray($contenido['posts']);
        $this->assertIsArray($contenido['reporte_financiero']['cifras']);
        $this->assertArrayHasKey('mes', $contenido['reporte_financiero']);
        $this->assertArrayHasKey('hoja_url', $contenido['reporte_financiero']);

        // Y tiene que poder sembrarse tal como está en el repo.
        (new ContenidoInicialSeeder)->run();
    }

    public function test_siembra_los_posts_de_convivencia_del_archivo(): void
    {
        $this->sembrar([
            'posts' => [
                [
                    'titulo' => 'Manejo de la basura',
                    'slug' => 'manejo-de-la-basura',
                    'publicado_en' => '2026-08-21',
                    'contenido' => "## Lineamientos\n\n- Solo los **Martes**.",
                ],
            ],
        ]);

        $this->assertDatabaseCount('posts', 1);

        $this->get(route('convivencia'))->assertSee('Manejo de la basura');

        // El post se pide por su dirección literal y no por `route()`: lo que se
        // siembra es la liga que se pega en el grupo de vecinos, y un `route()`
        // resuelve bien aunque el slug haya salido con otra forma.
        $this->get('/convivencia/manejo-de-la-basura')
            ->assertOk()
            ->assertSee('Manejo de la basura')
            ->assertSee('<strong>Martes</strong>', false);
    }

    public function test_sembrar_dos_veces_no_duplica_los_posts(): void
    {
        $contenido = [
            'posts' => [
                [
                    'titulo' => 'Manejo de la basura',
                    'slug' => 'manejo-de-la-basura',
                    'publicado_en' => '2026-08-21',
                    'contenido' => 'El cuarto de basura está a la entrada.',
                ],
            ],
        ];

        $this->sembrar($contenido);
        $this->sembrar($contenido);

        $this->assertDatabaseCount('posts', 1);
    }

    /**
     * El contrato que separa a los posts del Reporte financiero: aquél se
     * corrige desde el archivo, éste no. Un post es un texto largo que la Mesa
     * Directiva sigue puliendo desde el panel después de publicarlo, y el
     * despliegue siguiente no puede borrarle esa edición.
     */
    public function test_sembrar_de_nuevo_no_pisa_lo_que_se_edito_desde_el_panel(): void
    {
        $contenido = [
            'posts' => [
                [
                    'titulo' => 'Manejo de la basura',
                    'slug' => 'manejo-de-la-basura',
                    'publicado_en' => '2026-08-21',
                    'contenido' => 'El horario es de 7 am a 10 pm.',
                ],
            ],
        ];

        $this->sembrar($contenido);

        Post::query()->firstOrFail()->update([
            'titulo' => 'Manejo de la basura (corregido)',
            'contenido' => 'El horario es de 7 am a 9 pm.',
        ]);

        $this->sembrar($contenido);

        $this->assertDatabaseCount('posts', 1);

        $post = Post::query()->firstOrFail();

        $this->assertSame('Manejo de la basura (corregido)', $post->titulo);
        $this->assertSame('El horario es de 7 am a 9 pm.', $post->contenido);
    }

    public function test_un_post_sin_titulo_sin_contenido_o_sin_fecha_no_se_siembra(): void
    {
        $this->sembrar([
            'posts' => [
                ['titulo' => '', 'slug' => 'sin-titulo', 'publicado_en' => '2026-08-21', 'contenido' => 'Con texto.'],
                ['titulo' => 'Sin contenido', 'slug' => 'sin-contenido', 'publicado_en' => '2026-08-21', 'contenido' => ''],
                ['titulo' => 'Sin fecha', 'slug' => 'sin-fecha', 'contenido' => 'Con texto.'],
                ['titulo' => 'El único completo', 'slug' => 'el-unico-completo', 'publicado_en' => '2026-08-21', 'contenido' => 'Con texto.'],
            ],
        ]);

        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['slug' => 'el-unico-completo']);
    }

    /**
     * La ruta solo acepta minúsculas, dígitos y guiones. Una dirección pegada
     * con acentos o espacios sembraría un post publicado y en 404 a la vez, así
     * que se normaliza antes de guardarla.
     */
    public function test_la_direccion_se_normaliza_a_lo_que_la_ruta_acepta(): void
    {
        $this->sembrar([
            'posts' => [
                [
                    'titulo' => 'Uso de las áreas comunes',
                    'slug' => 'Uso de las Áreas Comunes',
                    'publicado_en' => '2026-08-21',
                    'contenido' => 'El mirador cierra a las 10 pm.',
                ],
            ],
        ]);

        $this->assertDatabaseHas('posts', ['slug' => 'uso-de-las-areas-comunes']);

        $this->get('/convivencia/uso-de-las-areas-comunes')->assertOk();
    }

    /**
     * Sin dirección el post no se salta: sale del título, que es de donde
     * saldría igual. Quien pega el material no tiene por qué saber que hay dos
     * campos para nombrar lo mismo.
     */
    public function test_sin_direccion_la_saca_del_titulo(): void
    {
        $this->sembrar([
            'posts' => [
                [
                    'titulo' => 'Manejo de la basura',
                    'publicado_en' => '2026-08-21',
                    'contenido' => 'El cuarto de basura está a la entrada.',
                ],
            ],
        ]);

        $this->assertDatabaseHas('posts', ['slug' => 'manejo-de-la-basura']);
    }

    /**
     * El primer post del sitio va en el archivo y no capturado a mano, para que
     * exista desde el primer despliegue. Se afirma sobre el contenido y no solo
     * sobre el título: el reglamento sirve si dice los días y el horario.
     */
    public function test_el_archivo_del_repo_publica_el_reglamento_de_la_basura(): void
    {
        (new ContenidoInicialSeeder)->run();

        $this->get('/convivencia/manejo-de-la-basura')
            ->assertOk()
            ->assertSee('Manejo de la basura')
            ->assertSee('Martes, Jueves, Sábado y Domingo', false)
            ->assertSee('7 am a 10 pm', false)
            ->assertSee('Lineamientos')
            ->assertSee('Actualización para residentes', false)
            // Las marcas de la transcripción nombran las hojas del escaneo, no
            // el contenido: no se publican.
            ->assertDontSee('Página 1', false)
            ->assertDontSee('Adobe Scan', false);
    }

    /**
     * @param  array<string, mixed>  $contenido
     */
    private function sembrar(array $contenido): void
    {
        $seeder = new ContenidoInicialSeeder;
        $seeder->contenido = $contenido;
        $seeder->run();
    }
}
