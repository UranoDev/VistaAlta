<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use App\Support\VentanaDeValidacion;
use Tests\TestCase;

/**
 * Los Términos de Servicio.
 *
 * De nvavista se cayeron tres secciones —registro de cuentas, cuotas y
 * suspensión de cuentas— porque aquí no existe ninguna de esas cosas. Lo que
 * se protege es que no regresen, que la moderación quede dicha con carácter de
 * término, y que la jurisdicción no vuelva a ser un corchete.
 */
class TerminosTest extends TestCase
{
    public function test_la_pagina_responde_sin_autenticacion(): void
    {
        $respuesta = $this->get(route('terminos'));

        $respuesta->assertOk();
        $respuesta->assertSee('Términos de Servicio', escape: false);
        $respuesta->assertSee('Aceptación de los términos', escape: false);
    }

    public function test_tiene_las_diez_secciones_del_articulado_mas_contacto(): void
    {
        $respuesta = $this->get(route('terminos'));

        foreach ([
            'Aceptación de los términos',
            'Objeto del sitio',
            'Validación por SMS',
            'Comunicados y avisos',
            'Obligaciones del Visitante',
            'Propiedad intelectual',
            'Limitación de responsabilidad',
            'Protección de datos personales',
            'Modificaciones a los Términos',
            'Legislación aplicable y jurisdicción',
            'Contacto',
        ] as $seccion) {
            $respuesta->assertSee($seccion, escape: false);
        }
    }

    public function test_no_menciona_registro_de_cuentas_ni_cuotas_ni_suspension(): void
    {
        $respuesta = $this->get(route('terminos'));

        $respuesta->assertDontSee('Registro y cuenta de usuario', escape: false);
        $respuesta->assertDontSee('Cuotas de mantenimiento y pagos', escape: false);
        $respuesta->assertDontSee('Suspensión y cancelación de cuentas', escape: false);
        $respuesta->assertDontSee('estado de cuenta', escape: false);
        $respuesta->assertDontSee('contraseña', escape: false);
    }

    /**
     * La moderación ocurre desde que existe la Cola de moderación, pero hasta
     * aquí no estaba dicha en ninguna parte con carácter de término.
     */
    public function test_dice_que_un_comentario_publico_pasa_por_revision_antes_de_aparecer(): void
    {
        $this->get(route('terminos'))
            ->assertSee('pasan por la revisión de la Mesa Directiva antes', escape: false)
            ->assertSee('pueden no publicarse', escape: false);
    }

    public function test_la_jurisdiccion_es_el_estado_de_queretaro(): void
    {
        $this->get(route('terminos'))
            ->assertSee('los tribunales competentes del estado de Querétaro', escape: false);
    }

    public function test_la_validacion_por_sms_declara_la_ventana_de_validacion(): void
    {
        $this->get(route('terminos'))
            ->assertSee(VentanaDeValidacion::MINUTOS.' minutos', escape: false);
    }

    public function test_la_fecha_y_el_correo_salen_de_la_configuracion(): void
    {
        config([
            'contenido.legal.actualizado_en' => '3 de marzo de 2027',
            'contenido.correo_contacto' => 'buzon@ejemplo.test',
        ]);

        $this->get(route('terminos'))
            ->assertSee('3 de marzo de 2027')
            ->assertSee('mailto:buzon@ejemplo.test', escape: false);
    }

    public function test_no_queda_ningun_corchete_sin_resolver_ni_franja_de_borrador(): void
    {
        $contenido = $this->get(route('terminos'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/\[[^\]]+\]/', strip_tags($contenido));
        $this->assertStringNotContainsString('pendiente de revisión legal', $contenido);
    }

    public function test_enlaza_al_aviso_de_privacidad(): void
    {
        $this->get(route('terminos'))->assertSee(route('privacidad'));
    }
}
