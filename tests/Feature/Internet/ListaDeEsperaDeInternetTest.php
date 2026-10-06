<?php

declare(strict_types=1);

namespace Tests\Feature\Internet;

use App\Models\SolicitudDeInternet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El formulario público de `/internet`: qué guarda, qué folio da y cuándo dice
 * que una propiedad ya estaba en la lista.
 */
class ListaDeEsperaDeInternetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function envio(array $cambios = []): array
    {
        return [
            'calle' => 'Margarita',
            'numero_oficial' => '128',
            'manzana' => '4',
            'lote' => '12',
            'celular' => '55 1234-5678',
            ...$cambios,
        ];
    }

    public function test_la_pagina_abre_sin_pedir_nada_y_no_se_deja_indexar(): void
    {
        $this->get(route('internet'))
            ->assertOk()
            ->assertSee('Lista de espera para la instalación')
            ->assertSee('noindex', false);
    }

    public function test_ofrece_las_seis_calles_como_opciones(): void
    {
        $respuesta = $this->get(route('internet'));

        foreach (['Clavel', 'Geranio', 'Malva', 'Margarita', 'Nube', 'Pensamiento'] as $calle) {
            $respuesta->assertSee('name="calle"', false)->assertSee('value="'.$calle.'"', false);
        }
    }

    public function test_una_propiedad_nueva_se_guarda_y_recibe_el_primer_folio(): void
    {
        $this->post(route('internet.store'), $this->envio())->assertRedirect(route('internet'));

        $solicitud = SolicitudDeInternet::query()->sole();

        $this->assertSame('INT-001', $solicitud->folio());
        $this->assertSame('Margarita', $solicitud->calle->value);
        $this->assertSame('5512345678', $solicitud->celular);
    }

    public function test_despues_de_anotarse_se_ve_el_folio_y_el_domicilio(): void
    {
        $this->followingRedirects()
            ->post(route('internet.store'), $this->envio())
            ->assertSee('INT-001')
            ->assertSee('Margarita 128, manzana 4, lote 12')
            ->assertDontSee('Anotarme en la lista');
    }

    public function test_los_folios_son_consecutivos(): void
    {
        $this->post(route('internet.store'), $this->envio());
        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '130', 'lote' => '13']));
        $this->post(route('internet.store'), $this->envio(['calle' => 'Nube', 'numero_oficial' => '45']));

        $this->assertSame(
            ['INT-001', 'INT-002', 'INT-003'],
            SolicitudDeInternet::query()->orderBy('numero')->get()->map->folio()->all(),
        );
    }

    public function test_el_folio_pasa_de_tres_digitos_sin_truncarse(): void
    {
        SolicitudDeInternet::factory()->create(['numero' => 999]);

        $this->post(route('internet.store'), $this->envio());

        $this->assertSame('INT-1000', SolicitudDeInternet::query()->latest('numero')->first()->folio());
    }

    public function test_un_mismo_celular_puede_anotar_varias_propiedades(): void
    {
        $this->post(route('internet.store'), $this->envio());
        $this->post(route('internet.store'), $this->envio(['calle' => 'Nube', 'numero_oficial' => '45', 'manzana' => '9', 'lote' => '3']));

        $this->assertDatabaseCount('solicitudes_de_internet', 2);
        $this->assertSame(1, SolicitudDeInternet::query()->distinct()->count('celular'));
    }

    public function test_una_propiedad_que_ya_esta_no_crea_otro_folio_y_dice_cual_tiene(): void
    {
        $this->post(route('internet.store'), $this->envio());

        $this->from(route('internet'))
            ->post(route('internet.store'), $this->envio(['celular' => '5599998888']))
            ->assertRedirect(route('internet'))
            ->assertSessionHasErrors(['domicilio' => 'Esta propiedad ya está en la lista con el folio INT-001.']);

        $this->assertDatabaseCount('solicitudes_de_internet', 1);
        $this->assertSame('5512345678', SolicitudDeInternet::query()->sole()->celular);
    }

    public function test_el_domicilio_se_compara_sin_importar_mayusculas_ni_espacios(): void
    {
        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '12a', 'lote' => '3']));

        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '  12 A ', 'lote' => '3']))
            ->assertSessionHasErrors('domicilio');

        $this->assertDatabaseCount('solicitudes_de_internet', 1);
        $this->assertSame('12A', SolicitudDeInternet::query()->sole()->numero_oficial);
    }

    public function test_cambiar_cualquiera_de_los_cuatro_datos_es_otra_propiedad(): void
    {
        $this->post(route('internet.store'), $this->envio());

        foreach ([['calle' => 'Nube'], ['numero_oficial' => '129'], ['manzana' => '5'], ['lote' => '13']] as $cambio) {
            $this->post(route('internet.store'), $this->envio($cambio))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('solicitudes_de_internet', 5);
    }

    public function test_el_folio_de_una_solicitud_borrada_no_cambia_a_las_demas(): void
    {
        $this->post(route('internet.store'), $this->envio());
        $this->post(route('internet.store'), $this->envio(['lote' => '13']));

        SolicitudDeInternet::query()->where('numero', 1)->delete();

        $this->post(route('internet.store'), $this->envio(['lote' => '14']));

        $this->assertSame([2, 3], SolicitudDeInternet::query()->orderBy('numero')->pluck('numero')->all());
    }

    public function test_acepta_la_lada_de_mexico_y_guarda_diez_digitos(): void
    {
        $this->post(route('internet.store'), $this->envio(['celular' => '+52 1 55 1234 5678']));

        $this->assertSame('5512345678', SolicitudDeInternet::query()->sole()->celular);
    }

    public function test_la_calle_y_el_celular_son_obligatorios_y_el_domicilio_tambien(): void
    {
        $this->post(route('internet.store'), [])
            ->assertSessionHasErrors(['calle', 'celular', 'numero_oficial', 'manzana', 'lote']);

        $this->assertDatabaseCount('solicitudes_de_internet', 0);
    }

    public function test_con_numero_oficial_basta_sin_manzana_ni_lote(): void
    {
        $this->post(route('internet.store'), $this->envio(['manzana' => '', 'lote' => '']))
            ->assertSessionHasNoErrors();

        $solicitud = SolicitudDeInternet::query()->sole();

        $this->assertSame('128', $solicitud->numero_oficial);
        $this->assertSame('', $solicitud->manzana);
        $this->assertSame('Margarita 128', $solicitud->domicilio());
    }

    public function test_con_manzana_y_lote_basta_sin_numero_oficial(): void
    {
        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Margarita, manzana 4, lote 12', SolicitudDeInternet::query()->sole()->domicilio());
    }

    public function test_sin_numero_oficial_la_manzana_y_el_lote_van_juntos(): void
    {
        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '', 'lote' => '']))
            ->assertSessionHasErrors(['lote' => 'Falta el lote. Si no lo tienes, escribe el número oficial.']);

        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '', 'manzana' => '']))
            ->assertSessionHasErrors(['manzana' => 'Falta la manzana. Si no la tienes, escribe el número oficial.']);

        $this->assertDatabaseCount('solicitudes_de_internet', 0);
    }

    public function test_sin_ninguna_forma_de_ubicar_la_propiedad_se_rechaza_con_un_mensaje_claro(): void
    {
        $this->post(route('internet.store'), $this->envio(['numero_oficial' => '', 'manzana' => '', 'lote' => '']))
            ->assertSessionHasErrors(['numero_oficial' => 'Escribe el número oficial, o bien la manzana y el lote.']);
    }

    public function test_una_propiedad_dada_solo_por_numero_oficial_tambien_se_detecta_repetida(): void
    {
        $this->post(route('internet.store'), $this->envio(['manzana' => '', 'lote' => '']));

        $this->post(route('internet.store'), $this->envio(['manzana' => '', 'lote' => '', 'celular' => '5599998888']))
            ->assertSessionHasErrors(['domicilio' => 'Esta propiedad ya está en la lista con el folio INT-001.']);

        $this->assertDatabaseCount('solicitudes_de_internet', 1);
    }

    public function test_el_acuse_solo_nombra_lo_que_se_dio(): void
    {
        $this->followingRedirects()
            ->post(route('internet.store'), $this->envio(['manzana' => '', 'lote' => '']))
            ->assertSee('Margarita 128')
            ->assertDontSee('manzana');
    }

    public function test_la_calle_es_de_lista_cerrada(): void
    {
        $this->post(route('internet.store'), $this->envio(['calle' => 'Mrgarita']))
            ->assertSessionHasErrors(['calle' => 'Elige una de las calles.']);
    }

    public function test_el_celular_mal_escrito_se_rechaza_en_espanol(): void
    {
        $this->post(route('internet.store'), $this->envio(['celular' => '123']))
            ->assertSessionHasErrors(['celular' => 'Escribe el celular a 10 dígitos.']);
    }

    public function test_al_regresar_con_errores_conserva_lo_que_ya_se_lleno(): void
    {
        $this->from(route('internet'))->post(route('internet.store'), $this->envio(['celular' => '123']));

        $this->get(route('internet'))
            ->assertSee('value="128"', false)
            ->assertSee('Escribe el celular a 10 dígitos.');
    }

    public function test_la_siguiente_propiedad_ya_trae_el_celular_escrito(): void
    {
        $this->post(route('internet.store'), $this->envio());

        // El acuse se consume al verlo; la vuelta siguiente es el formulario.
        $this->get(route('internet'));

        $this->get(route('internet'))->assertSee('value="5512345678"', false);
    }

    public function test_el_campo_trampa_no_guarda_nada(): void
    {
        $this->post(route('internet.store'), $this->envio(['sitio_web' => 'http://spam.example']))
            ->assertRedirect(route('internet'));

        $this->assertDatabaseCount('solicitudes_de_internet', 0);
    }

    public function test_el_envio_tiene_tope_por_ip(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->post(route('internet.store'), $this->envio(['lote' => (string) $i]))->assertRedirect(route('internet'));
        }

        $this->post(route('internet.store'), $this->envio(['lote' => '21']))->assertStatus(429);
    }
}
