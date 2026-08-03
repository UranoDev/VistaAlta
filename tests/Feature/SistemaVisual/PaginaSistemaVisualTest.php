<?php

namespace Tests\Feature\SistemaVisual;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
 * `/sistema-visual` es la única vista que instancia los ocho componentes de
 * Palette Receipt juntos, así que es donde un renombre a medias se delata en un
 * solo lugar. La ruta existe bajo pruebas porque su guard es
 * `if (! app()->isProduction())` y `phpunit.xml` fija `APP_ENV=testing`.
 */
class PaginaSistemaVisualTest extends TestCase
{
    public function test_la_pagina_se_sirve(): void
    {
        // Si algún componente no resuelve, Blade revienta al compilar y esto no
        // llega a 200.
        $respuesta = $this->get(route('sistema-visual'));

        $respuesta->assertOk();
        $respuesta->assertSee('Sistema visual «Palette Receipt»', escape: false);
    }

    public static function piezas(): array
    {
        // Una marca por componente: texto que solo aparece si esa pieza rindió
        // su contenido, no solo si su vista existe.
        return [
            'seccion' => ['Referencia interna'],
            'rotulo' => ['Periodo'],
            'tarjeta' => ['$1,350.00'],
            'renglon' => ['Actividades publicadas'],
            'sello' => ['Presentado'],
            'boton' => ['Enviar comentario'],
            'nota' => ['Tu teléfono quedó validado por 30 minutos.'],
            'campo' => ['Teléfono celular'],
        ];
    }

    #[DataProvider('piezas')]
    public function test_la_pagina_rinde_todas_las_piezas(string $marca): void
    {
        $respuesta = $this->get(route('sistema-visual'));

        $respuesta->assertOk();
        $respuesta->assertSee($marca, escape: false);
    }
}
