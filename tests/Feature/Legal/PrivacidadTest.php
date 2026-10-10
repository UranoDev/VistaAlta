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
    /**
     * Las dos garantías de la sección 6. El código ya las cumple
     * (`ComentarioPrivadoNoSeModera` y `VisibilidadEsDefinitiva`) y la interfaz
     * ya las promete; el Aviso es donde quedan por escrito.
     */
    public function test_declara_que_los_datos_de_contacto_no_se_publican_y_que_lo_privado_no_se_vuelve_publico(): void
    {
        $texto = $this->textoDelAviso();

        $this->assertStringContainsString('no se publican en ninguna parte del sitio: solo los ve la Administración', $texto);
        $this->assertStringContainsString('hacerse público después, por ningún medio', $texto);
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
     * El Aviso dice qué datos se piden y para qué, de forma genérica: sin nombrar
     * las pantallas del sitio ni los proveedores. Si volviera a nombrar un lugar
     * del sitio o un proveedor, tendría que cambiar cada vez que cambie uno.
     */
    public function test_describe_datos_y_finalidades_sin_nombrar_pantallas_ni_proveedores(): void
    {
        $texto = $this->textoDelAviso();

        foreach (['lista de espera', 'internet', 'registro de propietarios', 'Twilio', 'Amazon', 'Alinet', 'Propuesta'] as $nombre) {
            $this->assertStringNotContainsString($nombre, $texto, "El Aviso no debe nombrar «{$nombre}».");
        }
    }

    public function test_enumera_los_datos_que_se_piden(): void
    {
        $texto = $this->textoDelAviso();

        foreach ([
            'Su nombre completo',
            'Su número de teléfono celular',
            'Su correo electrónico',
            'El domicilio de una propiedad: la calle y el número oficial, o bien la manzana y el lote',
            'El tipo de propiedad: terreno, casa terminada o casa en construcción',
            'el nombre y el teléfono de un contacto de emergencia',
            'El texto del comentario que escribe',
            'una huella que no permite recuperarlo',
            'quién lo revisó, cuándo y, si la hay, una nota',
        ] as $dato) {
            $this->assertStringContainsString($dato, $texto);
        }

        // Ya no se pide cuántas personas viven en la propiedad.
        $this->assertStringNotContainsString('residentes', $texto);
        $this->assertStringNotContainsString('cuántas personas', $texto);
    }

    public function test_dice_para_que_se_usa_cada_dato(): void
    {
        $texto = $this->textoDelAviso();

        foreach ([
            'Saber quién es usted y cómo localizarlo',
            'Verificar que usted controla el celular y el correo que dio',
            'evitar que se registre dos veces',
            'Que la Administración revise y valide los registros',
            'para el sistema automatizado de pagos del fraccionamiento',
            'Avisar a las personas de contacto que usted indicó',
            'Cumplir con obligaciones legales aplicables',
        ] as $finalidad) {
            $this->assertStringContainsString($finalidad, $texto);
        }
    }

    public function test_dice_que_no_se_transfieren_datos_y_que_los_proveedores_solo_reciben_lo_necesario(): void
    {
        $texto = $this->textoDelAviso();

        $this->assertStringContainsString('No transferimos sus datos personales a terceros, salvo a las autoridades competentes', $texto);
        $this->assertStringContainsString('proveedores de alojamiento, de mensajería SMS y de correo electrónico', $texto);
        $this->assertStringContainsString('Reciben únicamente los datos que necesitan para prestar ese servicio', $texto);
        $this->assertStringContainsString('sin poder usarlos para otros fines', $texto);
    }

    /**
     * El plazo de conservación se cumple en código (`datos:depurar`) y sale de la
     * misma llave de configuración que el texto.
     */
    public function test_dice_cuanto_se_conservan_los_datos_y_el_plazo_sale_de_la_configuracion(): void
    {
        $this->assertStringContainsString(
            'hasta 2 años después de la última vez que se actualizaron o usaron. Pasado ese plazo se eliminan automáticamente',
            $this->textoDelAviso(),
        );

        config(['contenido.legal.conservacion_anos' => 3]);

        $this->assertStringContainsString('hasta 3 años después', $this->textoDelAviso());
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

    public function test_pide_que_quien_da_datos_de_otras_personas_tenga_su_autorizacion(): void
    {
        $this->assertStringContainsString(
            'cuenta con su autorización para hacerlo y que les dio a conocer este Aviso',
            $this->textoDelAviso(),
        );
    }

    public function test_los_terminos_dicen_que_verificar_no_acredita_ser_propietario(): void
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->get(route('terminos'))->getContent()), ENT_QUOTES | ENT_HTML5)));

        $this->assertStringContainsString('Recibir el registro de los propietarios', $texto);
        $this->assertStringContainsString('no acredita que sea propietario de los lotes que declaró', $texto);
        $this->assertStringContainsString('Eso lo revisa la Administración', $texto);
        $this->assertStringNotContainsString('no tiene padrón de colonos', $texto);
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
        foreach (['reporte-financiero', 'registro', 'actividades', 'vigilancia', 'administracion'] as $ruta) {
            $respuesta->assertSee(route($ruta));
        }
    }
}
