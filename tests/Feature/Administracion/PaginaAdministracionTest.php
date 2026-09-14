<?php

declare(strict_types=1);

namespace Tests\Feature\Administracion;

use Tests\TestCase;

/**
 * La página pública de Administración.
 *
 * Lo que más se cuida aquí es que el trámite se siga leyendo como trámite. Tres
 * de los cuatro pasos todavía no ocurren, y la tentación de escribir «ya
 * quedamos registrados» va a existir cada vez que alguien toque el texto: el
 * sello dice «Nombre autorizado» y no «Registrada», y eso se prueba.
 *
 * También se prueba el orden —el Comité antes de la Administración— porque es
 * una decisión y no una casualidad del maquetado: el contrapeso se lee antes
 * que a quien supervisa.
 */
class PaginaAdministracionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['contenido.administracion' => [
            'razon_social' => 'Fraccionamiento de Prueba',
            'tramite' => [
                ['titulo' => 'Autorización del nombre', 'detalle' => 'Ya salió.', 'estado' => 'listo'],
                ['titulo' => 'Firma del acta constitutiva', 'detalle' => 'Ante notario.', 'estado' => 'sigue'],
                ['titulo' => 'RFC de la asociación', 'detalle' => 'Después del acta.', 'estado' => 'falta'],
            ],
            'comite' => [
                ['nombre' => 'Claudia Arriola', 'cargo' => 'Integrante', 'hace' => null, 'foto' => null],
                ['nombre' => 'Rafael Rojas', 'cargo' => 'Integrante', 'hace' => null, 'foto' => 'rafael.jpg'],
            ],
            'cabeza' => [
                'nombre' => 'Lourdes Ríos',
                'cargo' => 'Administradora',
                'hace' => 'Coordina la operación del fraccionamiento.',
                'foto' => null,
            ],
            'integrantes' => [
                ['nombre' => 'Urano González', 'cargo' => 'Tesorero', 'hace' => 'Lleva las cuentas.', 'foto' => null],
            ],
        ]]);
    }

    public function test_la_pagina_responde(): void
    {
        $this->get(route('administracion'))->assertOk();
    }

    public function test_publica_el_nombre_autorizado_de_la_asociacion(): void
    {
        $this->get(route('administracion'))
            ->assertSee('Fraccionamiento de Prueba')
            ->assertSee('Nombre autorizado');
    }

    /**
     * El trámite está en curso, y la página no puede darlo por terminado. Es la
     * prueba que sostiene la decisión: lo único consumado es la autorización
     * del nombre.
     */
    public function test_no_afirma_que_la_asociacion_ya_quedo_registrada(): void
    {
        $respuesta = $this->get(route('administracion'));

        $respuesta->assertDontSee('Registrada', false);
        $respuesta->assertDontSee('ya quedó registrada');
    }

    public function test_publica_los_cuatro_estados_del_tramite_en_orden(): void
    {
        $this->get(route('administracion'))->assertSeeInOrder([
            'Autorización del nombre',
            'Listo',
            'Firma del acta constitutiva',
            'Sigue',
            'RFC de la asociación',
            'Falta',
        ]);
    }

    /**
     * El Comité va antes que la Administración: el contrapeso se lee antes que
     * a quien supervisa.
     */
    public function test_el_comite_de_vigilancia_se_lee_antes_que_la_administracion(): void
    {
        $this->get(route('administracion'))->assertSeeInOrder([
            'Es quien supervisa',
            'Claudia Arriola',
            'Es quien lleva el día a día',
            'Lourdes Ríos',
        ]);
    }

    public function test_publica_a_los_integrantes_de_los_dos_organos_con_su_cargo(): void
    {
        $this->get(route('administracion'))
            ->assertSee('Claudia Arriola')
            ->assertSee('Rafael Rojas')
            ->assertSee('Lourdes Ríos')
            ->assertSee('Administradora')
            ->assertSee('Urano González')
            ->assertSee('Tesorero');
    }

    /**
     * La línea de qué hace cada cargo vive abajo del organigrama, no dentro de
     * la tarjeta: es lo que mantiene las siete tarjetas del mismo alto.
     */
    public function test_lista_que_hace_cada_cargo_de_la_administracion(): void
    {
        $this->get(route('administracion'))
            ->assertSee('Qué hace cada quien')
            ->assertSee('Coordina la operación del fraccionamiento.')
            ->assertSee('Lleva las cuentas.');
    }

    /**
     * Quien puso foto sale con su foto; quien no, con su monograma. Las dos
     * tarjetas se ven igual de completas.
     */
    public function test_dibuja_foto_o_monograma_segun_lo_que_haya(): void
    {
        $respuesta = $this->get(route('administracion'));

        $respuesta->assertSee('img/administracion/rafael.jpg', false);
        $respuesta->assertSee('>CA<', false);
    }

    /**
     * A diferencia de Vigilancia, esta página sí se deja indexar: son cargos
     * electos, no trabajadores.
     */
    public function test_no_lleva_noindex(): void
    {
        $this->get(route('administracion'))->assertDontSee('noindex', false);
    }

    public function test_la_entrada_del_menu_apunta_a_la_pagina(): void
    {
        $this->get(route('administracion'))
            ->assertSee('Administración')
            ->assertSee(route('administracion'), false);
    }

    /**
     * Que la cabeza falte es un estado posible —entre una renuncia y la
     * Asamblea que la reponga—, y la página tiene que dibujarse sin ella en vez
     * de reventar.
     */
    public function test_se_dibuja_aunque_la_administracion_no_tenga_cabeza(): void
    {
        config(['contenido.administracion.cabeza' => null]);

        $this->get(route('administracion'))
            ->assertOk()
            ->assertDontSee('Lourdes Ríos')
            ->assertSee('Urano González');
    }

    public function test_publica_el_correo_de_contacto(): void
    {
        $this->get(route('administracion'))
            ->assertSee(config('contenido.correo_contacto'));
    }
}
