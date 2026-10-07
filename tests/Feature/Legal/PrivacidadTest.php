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

    /**
     * El Aviso no puede quedarse atrás del sitio: la lista de espera de internet
     * recaba el celular y el domicilio, y entrega la lista a Alinet. Un aviso que
     * calla datos que sí se piden es tan falso como uno que inventa los que no.
     */
    public function test_cubre_la_lista_de_espera_de_internet(): void
    {
        $texto = $this->textoDelAviso();

        $this->assertStringContainsString('lista de espera para la instalación de internet', $texto);
        $this->assertStringContainsString('El domicilio de la propiedad', $texto);
        $this->assertStringContainsString('No se le pide su nombre', $texto);
        $this->assertStringContainsString('folio consecutivo', $texto);
        $this->assertStringContainsString('El domicilio que anota en esa lista tampoco se publica', $texto);
    }

    /**
     * La lista no se entrega a nadie: quien se anota se pone en contacto con
     * Alinet por su cuenta. Si el Aviso, los Términos o el formulario volvieran a
     * decir que se le comparte algo a Alinet, estarían prometiendo una
     * transferencia que no existe.
     */
    public function test_no_dice_que_se_entregue_nada_a_alinet(): void
    {
        $this->assertStringNotContainsString('Alinet', $this->textoDelAviso());
        $this->assertStringNotContainsString('Alinet', strip_tags($this->get(route('terminos'))->getContent()));
        $this->assertStringNotContainsString('se le entregan', strip_tags($this->get(route('internet'))->getContent()));
        $this->assertStringNotContainsString('requiere su consentimiento, que usted otorga', $this->textoDelAviso());
    }

    /**
     * El texto de la página sin etiquetas y con los espacios colapsados: las
     * frases largas caen partidas entre renglones del archivo, y lo que se
     * protege aquí es lo que dicen, no dónde se cortan.
     */
    private function textoDelAviso(): string
    {
        $texto = html_entity_decode(strip_tags($this->get(route('privacidad'))->getContent()), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $texto));
    }

    public function test_el_formulario_de_internet_enlaza_el_aviso(): void
    {
        $this->get(route('internet'))
            ->assertSee('href="'.route('privacidad').'"', escape: false)
            ->assertSee('Al anotarte aceptas el', escape: false);
    }

    public function test_los_terminos_incluyen_la_lista_de_espera_entre_lo_que_hace_el_sitio(): void
    {
        $this->get(route('terminos'))
            ->assertSee('lista de espera para la instalación de internet', escape: false);
    }

    public function test_el_pie_enlaza_a_las_dos_paginas_legales_y_la_navegacion_no_cambia(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertSee(route('privacidad'));
        $respuesta->assertSee(route('terminos'));

        // El menú de arriba no cambia por estar en una página legal.
        foreach (['reporte-financiero', 'actividades', 'vigilancia', 'administracion', 'demanda'] as $ruta) {
            $respuesta->assertSee(route($ruta));
        }
    }
}
