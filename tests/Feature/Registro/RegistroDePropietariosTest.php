<?php

declare(strict_types=1);

namespace Tests\Feature\Registro;

use App\Models\ContactoDelRegistro;
use App\Models\LoteRegistrado;
use App\Models\RegistroDePropietario;
use App\Support\Registro\Ordinales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El formulario público de `/registro`: lo que guarda, lo que rechaza y que no
 * se deje encontrar. Lo que se guarda aquí es lo que luego se lee en el panel.
 */
class RegistroDePropietariosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function envio(array $cambios = []): array
    {
        return array_replace_recursive([
            'nombre' => 'Marta Ejemplo Pérez',
            'telefono' => '55 1234-5678',
            'correo' => 'Marta@Correo.com',
            'lotes' => [
                0 => [
                    'calle' => 'Margarita',
                    'numero_oficial' => '128',
                    'manzana' => '4',
                    'lote' => '12',
                    'situacion' => 'casa_terminada',
                ],
            ],
            'acepto_aviso' => '1',
        ], $cambios);
    }

    public function test_la_pagina_abre_sin_pedir_nada_y_no_se_deja_indexar(): void
    {
        $this->get(route('registro'))
            ->assertOk()
            ->assertSee('Registra tus lotes')
            ->assertSee('noindex', false);
    }

    public function test_un_registro_completo_se_guarda_con_sus_lotes_y_contactos(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [
                1 => [
                    'calle' => 'Nube',
                    'numero_oficial' => '45',
                    'manzana' => '9',
                    'lote' => '3',
                    'situacion' => 'en_construccion',
                ],
            ],
            'contactos' => [
                ['nombre' => 'Luis Ejemplo', 'telefono' => '5598765432', 'correo' => ''],
            ],
            'emergencia_nombre' => 'Ana Ejemplo',
            'emergencia_telefono' => '5511112222',
            'residentes' => '4',
        ]))->assertRedirect(route('registro.confirmar'));

        $registro = RegistroDePropietario::query()->sole();

        $this->assertSame('Marta Ejemplo Pérez', $registro->nombre);
        $this->assertSame('5512345678', $registro->telefono);
        $this->assertSame('marta@correo.com', $registro->correo);
        $this->assertSame(4, $registro->residentes);
        $this->assertSame('Ana Ejemplo', $registro->emergencia_nombre);
        $this->assertNotNull($registro->aceptado_en);
        $this->assertSame(config('contenido.legal.actualizado_en'), $registro->aviso_version);

        $this->assertSame(2, LoteRegistrado::query()->count());
        $this->assertSame(['Margarita 128', 'Nube 45'], $registro->lotes->map->etiqueta()->all());

        $contacto = ContactoDelRegistro::query()->sole();
        $this->assertSame('Luis Ejemplo', $contacto->nombre);
        $this->assertNull($contacto->correo);
    }

    public function test_despues_de_enviar_pide_el_codigo_y_no_el_formulario(): void
    {
        $this->followingRedirects()
            ->post(route('registro.store'), $this->envio())
            ->assertSee('Falta confirmarlo')
            ->assertSee('Código de 6 dígitos')
            ->assertDontSee('Enviar registro');
    }

    public function test_acepta_la_lada_de_mexico_y_guarda_diez_digitos(): void
    {
        $this->post(route('registro.store'), $this->envio(['telefono' => '+52 1 55 1234 5678']));

        $this->assertSame('5512345678', RegistroDePropietario::query()->sole()->telefono);
    }

    public function test_con_solo_correo_basta(): void
    {
        $this->post(route('registro.store'), $this->envio(['telefono' => '']))->assertSessionHasNoErrors();

        $this->assertNull(RegistroDePropietario::query()->sole()->telefono);
    }

    public function test_sin_correo_no_se_guarda_aunque_haya_telefono(): void
    {
        $this->post(route('registro.store'), $this->envio(['correo' => '']))
            ->assertSessionHasErrors(['correo' => 'Escribe tu correo. Ahí te mandamos el código para confirmar tu registro.']);

        $this->assertDatabaseCount('registros_de_propietarios', 0);
    }

    public function test_el_telefono_es_opcional(): void
    {
        $this->post(route('registro.store'), $this->envio(['telefono' => '']))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('registros_de_propietarios', 1);
    }

    public function test_un_contacto_adicional_tambien_necesita_telefono_o_correo(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'contactos' => [['nombre' => 'Luis Ejemplo', 'telefono' => '', 'correo' => '']],
        ]))->assertSessionHasErrors(['contactos.0.telefono', 'contactos.0.correo']);

        $this->assertDatabaseCount('registros_de_propietarios', 0);
    }

    public function test_pide_al_menos_un_lote(): void
    {
        $envio = $this->envio();
        unset($envio['lotes']);

        $this->post(route('registro.store'), $envio)->assertSessionHasErrors('lotes');
    }

    public function test_la_calle_y_la_situacion_son_de_lista_cerrada(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['calle' => 'Mrgarita', 'situacion' => 'castillo']],
        ]))->assertSessionHasErrors(['lotes.0.calle', 'lotes.0.situacion']);
    }

    public function test_un_lote_sin_ninguna_forma_de_ubicarlo_se_rechaza(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['numero_oficial' => '', 'manzana' => '', 'lote' => '']],
        ]))->assertSessionHasErrors([
            'lotes.0.numero_oficial' => 'Escribe el número oficial, o bien la manzana y el lote.',
        ]);

        $this->assertDatabaseCount('registros_de_propietarios', 0);
    }

    public function test_con_numero_oficial_el_lote_no_pide_manzana_ni_lote(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['numero_oficial' => '128', 'manzana' => '', 'lote' => '']],
        ]))->assertSessionHasNoErrors();

        $lote = LoteRegistrado::query()->sole();

        $this->assertSame('128', $lote->numero_oficial);
        $this->assertSame('', $lote->manzana);
        $this->assertSame('Margarita 128', $lote->etiqueta());
        $this->assertSame('', $lote->ubicacion());
    }

    public function test_con_manzana_y_lote_no_pide_numero_oficial(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['numero_oficial' => '']],
        ]))->assertSessionHasNoErrors();

        $lote = LoteRegistrado::query()->sole();

        $this->assertSame('', $lote->numero_oficial);
        $this->assertSame('Margarita', $lote->etiqueta());
        $this->assertSame('Mz 4, lote 12', $lote->ubicacion());
    }

    public function test_sin_numero_oficial_la_manzana_y_el_lote_van_juntos(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['numero_oficial' => '', 'lote' => '']],
        ]))->assertSessionHasErrors(['lotes.0.lote' => 'Falta el lote. Si no lo tienes, escribe el número oficial.']);

        $this->post(route('registro.store'), $this->envio([
            'lotes' => [0 => ['numero_oficial' => '', 'manzana' => '']],
        ]))->assertSessionHasErrors(['lotes.0.manzana' => 'Falta la manzana. Si no la tienes, escribe el número oficial.']);
    }

    public function test_cada_lote_se_evalua_por_separado(): void
    {
        $this->post(route('registro.store'), $this->envio([
            'lotes' => [
                0 => ['numero_oficial' => '128', 'manzana' => '', 'lote' => ''],
                1 => ['calle' => 'Nube', 'numero_oficial' => '', 'manzana' => '', 'lote' => '', 'situacion' => 'terreno'],
            ],
        ]))->assertSessionHasErrors(['lotes.1.numero_oficial'])->assertSessionDoesntHaveErrors(['lotes.0.manzana', 'lotes.0.lote']);
    }

    public function test_sin_aceptar_el_aviso_no_se_guarda(): void
    {
        $envio = $this->envio();
        unset($envio['acepto_aviso']);

        $this->post(route('registro.store'), $envio)->assertSessionHasErrors('acepto_aviso');

        $this->assertDatabaseCount('registros_de_propietarios', 0);
    }

    public function test_el_telefono_mal_escrito_se_rechaza_con_un_mensaje_en_espanol(): void
    {
        $respuesta = $this->from(route('registro'))->post(route('registro.store'), $this->envio(['telefono' => '123']));

        $respuesta->assertSessionHasErrors(['telefono' => 'Escribe el teléfono a 10 dígitos.']);
    }

    public function test_el_contacto_de_emergencia_pide_nombre_y_telefono_juntos(): void
    {
        $this->post(route('registro.store'), $this->envio(['emergencia_nombre' => 'Ana Ejemplo']))
            ->assertSessionHasErrors('emergencia_telefono');

        $this->post(route('registro.store'), $this->envio(['emergencia_telefono' => '5511112222']))
            ->assertSessionHasErrors('emergencia_nombre');
    }

    public function test_al_regresar_con_errores_conserva_lo_que_ya_se_llenó(): void
    {
        $this->from(route('registro'))
            ->post(route('registro.store'), $this->envio(['telefono' => '', 'correo' => '']))
            ->assertRedirect(route('registro'));

        $this->get(route('registro'))
            ->assertSee('Marta Ejemplo Pérez')
            ->assertSee('value="128"', false)
            ->assertSee('Revisa los campos marcados');
    }

    public function test_el_campo_trampa_finge_exito_y_no_guarda_nada(): void
    {
        $this->post(route('registro.store'), $this->envio(['sitio_web' => 'http://spam.example']))
            ->assertRedirect(route('registro.confirmar'));

        $this->assertDatabaseCount('registros_de_propietarios', 0);
    }

    public function test_el_envio_tiene_tope_por_ip(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('registro.store'), $this->envio())->assertRedirect(route('registro.confirmar'));
        }

        $this->post(route('registro.store'), $this->envio())->assertStatus(429);
    }

    public function test_cada_lote_se_titula_con_su_ordinal(): void
    {
        // El ordinal va en su propio <span> para que el script pueda cambiarlo.
        $this->get(route('registro'))->assertSee('<span data-ordinal>Primer</span> Lote', escape: false);

        // Al regresar con errores se pintan los renglones que ya traía, en orden.
        $this->from(route('registro'))->post(route('registro.store'), $this->envio([
            'telefono' => '',
            'correo' => '',
            'lotes' => [1 => ['calle' => 'Nube', 'numero_oficial' => '45', 'manzana' => '9', 'lote' => '3', 'situacion' => 'terreno']],
        ]));

        $this->get(route('registro'))
            ->assertSeeInOrder(['<span data-ordinal>Primer</span> Lote', '<span data-ordinal>Segundo</span> Lote'], escape: false)
            ->assertDontSee('<span data-ordinal>Tercer</span>', escape: false);
    }

    public function test_los_ordinales_llegan_hasta_el_maximo_de_lotes_del_registro(): void
    {
        $this->assertCount(20, Ordinales::LISTA);
        $this->assertSame('Primer', Ordinales::de(1));
        $this->assertSame('Tercer', Ordinales::de(3));
        $this->assertSame('Vigésimo', Ordinales::de(20));
    }

    public function test_la_pagina_entrega_los_ordinales_al_script(): void
    {
        $this->get(route('registro'))->assertSee('data-ordinales="[&quot;Primer&quot;,&quot;Segundo&quot;,&quot;Tercer&quot;', escape: false);
    }
}
