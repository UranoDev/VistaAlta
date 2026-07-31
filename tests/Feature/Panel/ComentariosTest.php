<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Enums\EstadoModeracion;
use App\Enums\Visibilidad;
use App\Filament\Resources\Comentarios\ComentariosResource;
use App\Filament\Resources\Comentarios\Pages\ListComentarios;
use App\Models\Comentario;
use App\Models\RecepcionDeComentarios;
use App\Models\User;
use App\Models\ViaDeRecepcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La única pantalla de Comentarios del panel: la Cola de moderación, los
 * Comentarios privados y el interruptor de Recepción de comentarios, fundidos.
 *
 * Lo que se publica aparece en la página de la Propuesta; lo que no, no existe de
 * cara a la Asamblea. Y un Comentario privado no se publica por ninguna vía: con
 * las dos listas fundidas eso ya no lo garantiza la consulta del recurso, así que
 * se prueba capa por capa.
 */
class ComentariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        // Lo que se afirme aquí sobre el interruptor de Recepción tiene que ser
        // del interruptor: con la vía de fábrica (WhatsApp) la página no muestra
        // el formulario aunque la recepción esté abierta, y «no se ve» dejaría de
        // probar nada. La Vía de recepción tiene su propia sección más abajo.
        ViaDeRecepcion::usarOtp();
    }

    // ── Una sola lista ────────────────────────────────────────────────────────

    public function test_la_pestana_de_entrada_es_la_cola(): void
    {
        $enCola = Comentario::factory()->enCola()->create(['comentario' => 'Una pregunta sobre la Propuesta.']);
        $publicado = Comentario::factory()->publicado()->create();
        $descartado = Comentario::factory()->descartado()->create();
        $privado = Comentario::factory()->privado()->create();

        Livewire::test(ListComentarios::class)
            ->assertSet('activeTab', 'en-cola')
            ->assertCanSeeTableRecords([$enCola])
            ->assertCanNotSeeTableRecords([$publicado, $descartado, $privado])
            ->assertSee('Una pregunta sobre la Propuesta.');
    }

    public function test_todos_lista_publicos_y_privados_juntos(): void
    {
        $enCola = Comentario::factory()->enCola()->create();
        $publicado = Comentario::factory()->publicado()->create();
        $descartado = Comentario::factory()->descartado()->create();
        $privado = Comentario::factory()->privado()->create();

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->assertCanSeeTableRecords([$enCola, $publicado, $descartado, $privado]);
    }

    public function test_la_pestana_de_privados_solo_trae_privados(): void
    {
        $privado = Comentario::factory()->privado()->create(['comentario' => 'Dirigido solo a la Mesa Directiva.']);
        $enCola = Comentario::factory()->enCola()->create();
        $publicado = Comentario::factory()->publicado()->create();

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'privados')
            ->assertCanSeeTableRecords([$privado])
            ->assertCanNotSeeTableRecords([$enCola, $publicado])
            ->assertSee('Dirigido solo a la Mesa Directiva.');
    }

    /**
     * Un privado trae `estado = null` a propósito. La columna tiene que decir
     * «Privado» y no «—», que se lee como dato faltante.
     */
    public function test_un_privado_se_distingue_de_un_vistazo(): void
    {
        $privado = Comentario::factory()->privado()->create();
        $enCola = Comentario::factory()->enCola()->create();

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->assertTableColumnFormattedStateSet('estado', 'Privado', $privado)
            ->assertTableColumnFormattedStateSet('estado', 'En cola', $enCola);
    }

    // ── Publicar y descartar ──────────────────────────────────────────────────

    public function test_publicar_lo_pone_en_la_pagina_de_la_propuesta(): void
    {
        $comentario = Comentario::factory()->enCola()->create(['comentario' => 'Ya publicado por la Mesa Directiva.']);

        Livewire::test(ListComentarios::class)
            ->callTableAction('publicar', $comentario)
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoModeracion::Publicado, $comentario->refresh()->estado);

        auth()->logout();
        $this->get(route('propuesta'))->assertSee('Ya publicado por la Mesa Directiva.');
    }

    public function test_descartar_lo_saca_de_la_cola_sin_publicarlo(): void
    {
        $comentario = Comentario::factory()->enCola()->create(['comentario' => 'Descartado por la Mesa Directiva.']);

        Livewire::test(ListComentarios::class)
            ->callTableAction('descartar', $comentario)
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoModeracion::Descartado, $comentario->refresh()->estado);

        auth()->logout();
        $this->get(route('propuesta'))->assertDontSee('Descartado por la Mesa Directiva.');
    }

    public function test_un_descarte_por_error_se_puede_publicar_despues(): void
    {
        $comentario = Comentario::factory()->descartado()->create(['comentario' => 'Rescatado del descarte.']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'descartados')
            ->assertCanSeeTableRecords([$comentario])
            ->callTableAction('publicar', $comentario)
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoModeracion::Publicado, $comentario->refresh()->estado);
    }

    public function test_lo_que_espera_en_la_cola_no_lo_ve_nadie_mas(): void
    {
        Comentario::factory()->enCola()->create(['comentario' => 'Todavía sin publicar.']);

        auth()->logout();
        $this->get(route('propuesta'))->assertDontSee('Todavía sin publicar.');
    }

    public function test_publicar_en_lote(): void
    {
        $comentarios = Comentario::factory()->enCola()->count(3)->create();

        Livewire::test(ListComentarios::class)
            ->callTableBulkAction('publicar', $comentarios)
            ->assertHasNoActionErrors();

        foreach ($comentarios as $comentario) {
            $this->assertSame(EstadoModeracion::Publicado, $comentario->refresh()->estado);
        }
    }

    /**
     * El panel no es un editor de comentarios: la Mesa Directiva decide si se
     * publican, no qué dicen.
     */
    public function test_el_panel_no_crea_ni_edita_ni_borra_comentarios(): void
    {
        $comentario = Comentario::factory()->enCola()->create();

        $this->assertFalse(ComentariosResource::canCreate());
        $this->assertFalse(ComentariosResource::canEdit($comentario));
        $this->assertFalse(ComentariosResource::canDelete($comentario));

        Livewire::test(ListComentarios::class)
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete');
    }

    // ── Un privado no se publica: las tres capas ──────────────────────────────

    /**
     * Capa 1: las acciones no se ofrecen. En la misma tabla que los públicos, un
     * privado no muestra ni publicar ni descartar.
     */
    public function test_un_privado_no_ofrece_publicar_ni_descartar(): void
    {
        $privado = Comentario::factory()->privado()->create();
        $publico = Comentario::factory()->enCola()->create();

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->assertTableActionHidden('publicar', $privado)
            ->assertTableActionHidden('descartar', $privado)
            ->assertTableActionDoesNotExist('edit', record: $privado)
            ->assertTableActionDoesNotExist('delete', record: $privado)
            // Que se esconden por privado, no porque la pantalla no las tenga.
            ->assertTableActionVisible('publicar', $publico)
            ->assertTableActionVisible('descartar', $publico);
    }

    /**
     * Capa 2: no lleva casilla utilizable, así que la selección mixta no existe
     * en la interfaz.
     */
    public function test_un_privado_no_se_puede_seleccionar(): void
    {
        $privado = Comentario::factory()->privado()->create();
        $publico = Comentario::factory()->enCola()->create();

        $componente = Livewire::test(ListComentarios::class)->set('activeTab', 'todos');

        $tabla = $componente->instance()->getTable();

        $this->assertFalse($tabla->isRecordSelectable($privado));
        $this->assertTrue($tabla->isRecordSelectable($publico));

        // La celda del privado queda en blanco, no con una casilla gris: Filament
        // envuelve el `input` entero en la comprobación de seleccionable, así que
        // el API estándar ya da lo que se pidió y no hace falta sobrescribir la
        // vista de la celda de selección.
        $html = $componente->html();

        $this->assertSame(1, substr_count($html, 'fi-ta-record-checkbox'));
        $this->assertStringContainsString('value="'.$publico->getKey().'"', $html);
    }

    public function test_seleccionar_todo_en_todos_deja_fuera_a_los_privados(): void
    {
        $privado = Comentario::factory()->privado()->create();
        $enCola = Comentario::factory()->enCola()->create();
        $publicado = Comentario::factory()->publicado()->create();

        $seleccionables = Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->instance()
            ->getAllSelectableTableRecordKeys();

        $this->assertEqualsCanonicalizing(
            [(string) $enCola->getKey(), (string) $publicado->getKey()],
            $seleccionables,
        );
        $this->assertNotContains((string) $privado->getKey(), $seleccionables);
    }

    /**
     * Capa 3: el filtro del lado del servidor. Una petición armada a mano puede
     * traer el ID de un privado aunque la casilla no se ofrezca; el lote lo tiene
     * que dejar fuera **antes** de iterar, sin reventar con
     * `ComentarioPrivadoNoSeModera` a media iteración y sin dejar publicada solo
     * una parte.
     */
    public function test_un_lote_armado_a_mano_con_un_privado_no_lo_publica_ni_revienta(): void
    {
        $privado = Comentario::factory()->privado()->create(['comentario' => 'Sigue siendo privado.']);
        $publico = Comentario::factory()->enCola()->create(['comentario' => 'Este sí se publica.']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->callTableBulkAction('publicar', [$privado, $publico])
            ->assertHasNoActionErrors();

        $privado->refresh();

        $this->assertSame(Visibilidad::Privado, $privado->visibilidad);
        $this->assertNull($privado->estado);
        $this->assertSame(EstadoModeracion::Publicado, $publico->refresh()->estado);

        auth()->logout();
        $this->get(route('propuesta'))
            ->assertDontSee('Sigue siendo privado.')
            ->assertSee('Este sí se publica.');
    }

    public function test_un_descarte_en_lote_armado_a_mano_tampoco_toca_al_privado(): void
    {
        $privado = Comentario::factory()->privado()->create();
        $publico = Comentario::factory()->enCola()->create();

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->callTableBulkAction('descartar', [$privado, $publico])
            ->assertHasNoActionErrors();

        $this->assertNull($privado->refresh()->estado);
        $this->assertSame(EstadoModeracion::Descartado, $publico->refresh()->estado);
    }

    // ── El teléfono ───────────────────────────────────────────────────────────

    public function test_la_lista_muestra_el_telefono_bajo_el_nombre(): void
    {
        Comentario::factory()->enCola()->create([
            'nombre' => 'Rosa Iturbe',
            'telefono' => '5531269267',
        ]);

        Livewire::test(ListComentarios::class)
            ->assertSee('Rosa Iturbe')
            ->assertSee('5531269267');
    }

    public function test_el_detalle_muestra_el_telefono_en_publicos_y_privados(): void
    {
        $publico = Comentario::factory()->enCola()->create(['telefono' => '5531269267']);
        $privado = Comentario::factory()->privado()->create(['telefono' => '4491234567']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->callTableAction('view', $publico)
            ->assertSee('5531269267');

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'privados')
            ->callTableAction('view', $privado)
            ->assertSee('4491234567');
    }

    public function test_buscar_un_telefono_devuelve_sus_comentarios_publicos_y_privados(): void
    {
        $suPublico = Comentario::factory()->enCola()->create(['telefono' => '5531269267']);
        $suPrivado = Comentario::factory()->privado()->create(['telefono' => '5531269267']);
        $deOtro = Comentario::factory()->enCola()->create(['telefono' => '4491234567']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->searchTable('5531269267')
            ->assertCanSeeTableRecords([$suPublico, $suPrivado])
            ->assertCanNotSeeTableRecords([$deOtro]);
    }

    /**
     * Los teléfonos se guardan en puros dígitos, así que el término de búsqueda se
     * normaliza igual antes de comparar: el mismo celular escrito de cualquier
     * forma encuentra lo mismo.
     */
    public function test_el_mismo_telefono_escrito_de_tres_formas_encuentra_lo_mismo(): void
    {
        $suyo = Comentario::factory()->enCola()->create(['telefono' => '5531269267']);
        $deOtro = Comentario::factory()->enCola()->create(['telefono' => '4491234567']);

        foreach (['55-3126-9267', '55 3126 9267', '5531269267'] as $termino) {
            Livewire::test(ListComentarios::class)
                ->set('activeTab', 'todos')
                ->searchTable($termino)
                ->assertCanSeeTableRecords([$suyo])
                ->assertCanNotSeeTableRecords([$deOtro]);
        }
    }

    /**
     * En producción conviven las dos formas por `TWILIO_PAIS_LADA`: el guardado
     * con lada se encuentra igual buscando los 10 dígitos, porque la comparación
     * es por contención.
     */
    public function test_un_numero_guardado_con_lada_se_encuentra_por_sus_diez_digitos(): void
    {
        $conLada = Comentario::factory()->enCola()->create(['telefono' => '+525531269267']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->searchTable('5531269267')
            ->assertCanSeeTableRecords([$conLada]);
    }

    public function test_la_busqueda_sigue_encontrando_por_nombre(): void
    {
        $rosa = Comentario::factory()->enCola()->create(['nombre' => 'Rosa Iturbe']);
        $otro = Comentario::factory()->enCola()->create(['nombre' => 'Joaquín Vela']);

        Livewire::test(ListComentarios::class)
            ->searchTable('Iturbe')
            ->assertCanSeeTableRecords([$rosa])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    /**
     * Los `orWhere` de la búsqueda van agrupados: sin el paréntesis el `or` se
     * escaparía y se llevaría por delante el filtro de la pestaña activa.
     */
    public function test_buscar_dentro_de_una_pestana_respeta_la_pestana(): void
    {
        $suPrivado = Comentario::factory()->privado()->create(['telefono' => '5531269267']);
        $suPublico = Comentario::factory()->enCola()->create(['telefono' => '5531269267']);

        Livewire::test(ListComentarios::class)
            ->set('activeTab', 'privados')
            ->searchTable('5531269267')
            ->assertCanSeeTableRecords([$suPrivado])
            ->assertCanNotSeeTableRecords([$suPublico]);
    }

    // ── El interruptor de recepción, en la misma pantalla ─────────────────────

    public function test_la_pantalla_muestra_que_la_recepcion_nace_abierta(): void
    {
        Livewire::test(ListComentarios::class)->assertSet('abierta', true);
    }

    public function test_el_interruptor_cierra_la_recepcion_desde_esta_pantalla(): void
    {
        Livewire::test(ListComentarios::class)->set('abierta', false);

        $this->assertFalse(RecepcionDeComentarios::estaAbierta());

        auth()->logout();
        $this->get(route('propuesta'))->assertDontSee('Enviarme el código');
    }

    public function test_el_interruptor_la_vuelve_a_abrir(): void
    {
        RecepcionDeComentarios::cerrar();

        Livewire::test(ListComentarios::class)
            ->assertSet('abierta', false)
            ->set('abierta', true);

        $this->assertTrue(RecepcionDeComentarios::estaAbierta());

        auth()->logout();
        $this->get(route('propuesta'))->assertSee('Enviarme el código');
    }

    /**
     * El error fácil: cerrar la recepción creyendo que retira lo publicado.
     */
    public function test_cerrar_desde_el_panel_no_despublica_nada(): void
    {
        Comentario::factory()->publicado()->create(['comentario' => 'Publicado antes de cerrar.']);

        Livewire::test(ListComentarios::class)->set('abierta', false);

        auth()->logout();
        $this->get(route('propuesta'))->assertSee('Publicado antes de cerrar.');
    }

    /**
     * Cerrar la recepción tampoco toca la Cola de moderación: lo que esperaba ahí
     * sigue esperando, y se puede seguir publicando.
     */
    public function test_cerrar_no_vacia_la_cola_de_moderacion(): void
    {
        $enCola = Comentario::factory()->enCola()->create();

        Livewire::test(ListComentarios::class)->set('abierta', false);

        $enCola->publicar();

        auth()->logout();
        $this->get(route('propuesta'))->assertSee($enCola->comentario);
    }

    /**
     * Las dos confusiones que el interruptor existe para evitar siguen escritas en
     * la pantalla, no solo en la documentación.
     */
    public function test_la_pantalla_conserva_los_dos_callouts_del_interruptor(): void
    {
        Livewire::test(ListComentarios::class)
            ->assertSee('Cerrarla no despublica nada')
            ->assertSee('La cola no se atiende sola')
            ->assertSee('indefinidamente');
    }

    // ── La Vía de recepción, en la misma pantalla ────────────────────────────

    public function test_la_pantalla_llega_con_la_via_puesta(): void
    {
        Livewire::test(ListComentarios::class)
            ->assertSet('via', 'otp')
            ->assertSet('numeroDeWhatsApp', ViaDeRecepcion::NUMERO_DE_FABRICA);
    }

    public function test_el_selector_mueve_la_recepcion_a_whatsapp(): void
    {
        Livewire::test(ListComentarios::class)->set('via', 'whatsapp');

        $this->assertTrue(ViaDeRecepcion::actual()->esWhatsApp());

        auth()->logout();
        $this->get(route('propuesta'))
            ->assertSee('Escribir por WhatsApp')
            ->assertDontSee('Enviarme el código', escape: false);
    }

    /**
     * El punto del interruptor: cuando Twilio destrabe el registro del remitente,
     * volver al canal bueno se hace desde aquí y sin desplegar nada.
     */
    public function test_el_selector_regresa_la_recepcion_al_sitio(): void
    {
        ViaDeRecepcion::usarWhatsApp();

        Livewire::test(ListComentarios::class)
            ->assertSet('via', 'whatsapp')
            ->set('via', 'otp');

        $this->assertTrue(ViaDeRecepcion::actual()->esOtp());

        auth()->logout();
        $this->get(route('propuesta'))
            ->assertSee('Enviarme el código', escape: false)
            ->assertDontSee('Escribir por WhatsApp');
    }

    public function test_el_numero_de_whatsapp_se_captura_desde_el_panel(): void
    {
        Livewire::test(ListComentarios::class)
            ->set('via', 'whatsapp')
            ->set('numeroDeWhatsApp', '+52 33 1111 2222');

        $this->assertSame('523311112222', ViaDeRecepcion::actual()->numeroDeWhatsApp());

        auth()->logout();
        $this->get(route('propuesta'))->assertSee('https://wa.me/523311112222?text=', escape: false);
    }

    /**
     * Un enlace de `wa.me` mal formado no falla en el panel: falla en el celular
     * de un colono que ya se fue a otra pantalla.
     */
    public function test_un_numero_que_no_se_puede_marcar_no_se_guarda(): void
    {
        ViaDeRecepcion::cambiarNumeroDeWhatsApp('523311112222');

        Livewire::test(ListComentarios::class)->set('numeroDeWhatsApp', '55 31');

        $this->assertSame('523311112222', ViaDeRecepcion::actual()->numeroDeWhatsApp());
    }

    public function test_cambiar_de_via_no_despublica_nada_ni_vacia_la_cola(): void
    {
        Comentario::factory()->publicado()->create(['comentario' => 'Publicado antes de cambiar de vía.']);
        $enCola = Comentario::factory()->enCola()->create();

        Livewire::test(ListComentarios::class)->set('via', 'whatsapp');

        $this->assertSame(1, Comentario::enCola()->count());

        auth()->logout();
        $this->get(route('propuesta'))->assertSee('Publicado antes de cambiar de vía.');

        $enCola->publicar();
        $this->get(route('propuesta'))->assertSee($enCola->comentario);
    }

    /**
     * La consecuencia que no es obvia de la vía de WhatsApp: ahí la visibilidad
     * —que es definitiva— la marca quien captura, no el autor. Tiene que estar
     * escrito en la pantalla donde se mueve el interruptor.
     */
    public function test_la_pantalla_advierte_quien_decide_la_visibilidad_en_whatsapp(): void
    {
        Livewire::test(ListComentarios::class)
            ->assertSee('En WhatsApp, la visibilidad la marcas tú')
            ->assertSee('respeta lo que haya pedido');
    }

    // ── Una sola entrada, no tres ────────────────────────────────────────────

    public function test_las_tres_pantallas_anteriores_dejan_de_existir(): void
    {
        // Los nombres van como cadena a propósito: son clases que ya no existen,
        // así que no hay `use` que las importe.
        $anteriores = [
            'App\Filament\Resources\ColaDeModeracion\ColaDeModeracionResource',
            'App\Filament\Resources\ComentariosPrivados\ComentariosPrivadosResource',
            'App\Filament\Pages\RecepcionDeComentarios',
        ];

        foreach ($anteriores as $clase) {
            $this->assertFalse(class_exists($clase), "La clase {$clase} debería haber dejado de existir.");
        }

        foreach (['cola-de-moderacion', 'comentarios-privados', 'recepcion-de-comentarios'] as $ruta) {
            $this->get("/admin/{$ruta}")->assertNotFound();
        }
    }

    /**
     * «Liberar» ya no es la palabra: el código interno siempre dijo publicar y
     * ahora la interfaz también.
     */
    public function test_la_interfaz_no_dice_liberar(): void
    {
        Comentario::factory()->enCola()->create();

        $respuesta = Livewire::test(ListComentarios::class)
            ->set('activeTab', 'todos')
            ->assertSee('Publicar');

        $this->assertStringNotContainsStringIgnoringCase('liberar', $respuesta->html());
    }
}
