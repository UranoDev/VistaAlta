<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginasPublicasTest extends TestCase
{
    // La Propuesta lista los Comentarios públicos, así que ya toca la base.
    use RefreshDatabase;

    public static function rutasPublicas(): array
    {
        return [
            'Propuesta' => ['propuesta', 'Propuesta'],
            'Actividades' => ['actividades', 'Actividades'],
            'Convivencia' => ['convivencia', 'Convivencia'],
            'Reporte financiero' => ['reporte-financiero', 'Reporte financiero'],
            'Vigilancia' => ['vigilancia', 'Quién cuida Vista Alta'],
            'Administración' => ['administracion', 'Quiénes servimos a Vista Alta'],
            'Demanda' => ['demanda', 'Faltan tus comprobantes'],
        ];
    }

    #[DataProvider('rutasPublicas')]
    public function test_las_paginas_publicas_se_sirven_sin_autenticacion(string $ruta, string $titulo): void
    {
        $respuesta = $this->get(route($ruta));

        $respuesta->assertOk();
        $respuesta->assertSee($titulo, escape: false);
    }

    /**
     * Se pregunta desde Convivencia y no desde la propia página de cada
     * entrada: el layout imprime la URL de la página actual en `og:url`, así
     * que preguntar desde Propuesta por la liga de Propuesta pasaría aunque el
     * menú no la trajera. Convivencia no está en juego en ninguna de las dos
     * afirmaciones de abajo.
     */
    public function test_el_layout_trae_la_navegacion_de_todas_las_paginas(): void
    {
        $respuesta = $this->get(route('convivencia'));

        $respuesta->assertSee(route('actividades'));
        $respuesta->assertSee(route('convivencia'));
        $respuesta->assertSee(route('reporte-financiero'));
        $respuesta->assertSee(route('vigilancia'));
        $respuesta->assertSee(route('administracion'));
        $respuesta->assertSee(route('demanda'));
    }

    /**
     * Propuesta salió del menú al quedar autorizado el nombre de la asociación
     * (URVA-99): lo que sometía a consideración ya está en trámite, y por dónde
     * va se rinde en Administración.
     *
     * La página **no** se retiró —sigue publicada y sigue recibiendo
     * Comentarios—, así que esta prueba mide las dos mitades: que no esté en la
     * navegación, y que siga habiendo por dónde llegar.
     */
    public function test_propuesta_salio_del_menu_pero_sigue_alcanzable(): void
    {
        $this->get(route('convivencia'))->assertDontSee(route('propuesta'));

        $this->get(route('actividades'))->assertSee(route('propuesta'));
        $this->get(route('propuesta'))->assertOk();
    }
}
