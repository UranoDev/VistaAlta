<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use Tests\TestCase;

/**
 * El Aviso de Privacidad.
 *
 * Lo que se protege aquí no es la redacción sino las tres cosas que el
 * documento no puede volver a decir mal: que solo describa datos que el sitio
 * de verdad recaba, que declare las dos garantías que el código ya cumple, y
 * que no quede ningún corchete sin resolver — sin franja de borrador que
 * avise, un marcador sin llenar se lee como texto vigente.
 */
class PrivacidadTest extends TestCase
{
    public function test_la_pagina_responde_sin_autenticacion(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertOk();
        $respuesta->assertSee('Aviso de Privacidad', escape: false);
        $respuesta->assertSee('Identidad y domicilio del responsable', escape: false);
        $respuesta->assertSee('Mecanismos para el ejercicio de derechos ARCO', escape: false);
    }

    public function test_describe_solo_los_datos_que_el_sitio_recaba(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertSee('número de teléfono celular', escape: false);
        $respuesta->assertSee('nombre con el que decide firmar', escape: false);
        $respuesta->assertSee('texto del comentario', escape: false);
        $respuesta->assertSee('dirección IP', escape: false);

        // Nada de lo que nvavista pedía y este sitio no: correo, cuentas, pagos.
        $respuesta->assertDontSee('correo electrónico en el que', escape: false);
        $respuesta->assertDontSee('cuenta de usuario', escape: false);
        $respuesta->assertDontSee('cuotas de mantenimiento', escape: false);
    }

    /**
     * Las dos garantías de la sección 6. El código ya las cumple
     * (`ComentarioPrivadoNoSeModera` y `VisibilidadEsDefinitiva`) y la interfaz
     * ya las promete; el Aviso es donde quedan por escrito.
     */
    public function test_declara_que_el_telefono_no_se_publica_y_que_lo_privado_no_se_vuelve_publico(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertSee('no se publica en ninguna parte del sitio', escape: false);
        $respuesta->assertSee('hacerse público después, por ningún medio', escape: false);
    }

    public function test_la_seccion_de_cookies_describe_las_dos_que_existen_y_no_inventa_analitica(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertSee('cookie de sesión', escape: false);
        $respuesta->assertSee('30 minutos', escape: false);
        $respuesta->assertSee('no utiliza herramientas de analítica', escape: false);
        $respuesta->assertDontSee('web beacons u otras tecnologías', escape: false);
    }

    public function test_la_fecha_y_el_correo_salen_de_la_configuracion(): void
    {
        config([
            'contenido.legal.actualizado_en' => '3 de marzo de 2027',
            'contenido.correo_contacto' => 'buzon@ejemplo.test',
        ]);

        $this->get(route('privacidad'))
            ->assertSee('3 de marzo de 2027')
            ->assertSee('mailto:buzon@ejemplo.test', escape: false);
    }

    /**
     * Sin la franja de "pendiente de revisión legal" que traen las páginas de
     * nvavista, el documento se lee como vigente. Un corchete sin resolver ahí
     * ya no es una nota al margen: es texto publicado.
     */
    public function test_no_queda_ningun_corchete_sin_resolver_ni_franja_de_borrador(): void
    {
        $contenido = $this->get(route('privacidad'))->getContent();

        $this->assertDoesNotMatchRegularExpression('/\[[^\]]+\]/', strip_tags($contenido));
        $this->assertStringNotContainsString('pendiente de revisión legal', $contenido);
    }

    public function test_el_pie_enlaza_a_las_dos_paginas_legales_y_la_navegacion_no_cambia(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertSee(route('privacidad'));
        $respuesta->assertSee(route('terminos'));

        // El menú de arriba sigue con las mismas cuatro entradas.
        foreach (['propuesta', 'actividades', 'reporte-financiero', 'demanda'] as $ruta) {
            $respuesta->assertSee(route($ruta));
        }
    }
}
