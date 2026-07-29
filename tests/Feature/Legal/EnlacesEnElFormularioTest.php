<?php

declare(strict_types=1);

namespace Tests\Feature\Legal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El Aviso tiene que estar disponible donde se recaban los datos, y ese lugar
 * es la primera rama del formulario de Comentarios: la que pide el celular.
 *
 * En las otras dos —el código pendiente y el teléfono ya validado— ya no se
 * está recabando nada, así que la línea no va ahí: repetirla las volvería
 * ruido y diluiría el aviso justo donde sí cuenta.
 *
 * Y sin casilla que marcar. Es el consentimiento tácito que contempla la
 * sección 9 del Aviso.
 */
class EnlacesEnElFormularioTest extends TestCase
{
    // La Propuesta lista los Comentarios públicos, así que toca la base.
    use RefreshDatabase;

    private const AVISO = 'Al validar tu teléfono aceptas el';

    public function test_la_rama_que_pide_el_celular_enlaza_al_aviso_y_a_los_terminos(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee(self::AVISO, escape: false);
        $respuesta->assertSee(route('privacidad'));
        $respuesta->assertSee(route('terminos'));
    }

    public function test_la_rama_del_codigo_pendiente_no_repite_el_aviso(): void
    {
        $this->withSession(['comentario.telefono' => '4421234567'])
            ->get(route('propuesta'))
            ->assertDontSee(self::AVISO, escape: false);
    }

    /**
     * No hay casilla de consentimiento en ninguna rama: el formulario ya trae
     * tres pasos y el consentimiento aquí es tácito.
     */
    public function test_no_hay_casilla_que_marcar(): void
    {
        $this->get(route('propuesta'))->assertDontSee('type="checkbox"', escape: false);
    }
}
