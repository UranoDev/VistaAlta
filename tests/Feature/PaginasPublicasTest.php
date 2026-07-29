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
            'Reporte financiero' => ['reporte-financiero', 'Reporte financiero'],
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

    public function test_el_layout_trae_la_navegacion_de_todas_las_paginas(): void
    {
        $respuesta = $this->get(route('propuesta'));

        $respuesta->assertSee(route('propuesta'));
        $respuesta->assertSee(route('actividades'));
        $respuesta->assertSee(route('reporte-financiero'));
        $respuesta->assertSee(route('demanda'));
    }
}
