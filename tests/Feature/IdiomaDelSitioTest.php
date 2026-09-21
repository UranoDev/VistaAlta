<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * El sitio está en español, y eso no lo decide el servidor (URVA-108).
 *
 * El idioma se leía del `.env`, que es como viene Laravel de fábrica. El `.env`
 * de producción se llenó a mano y no traía la llave, así que la aplicación cayó
 * en inglés por omisión y la página de Vigilancia publicó «Sunday 20 de
 * September de 2026» mezclado con el resto del texto en español. No falló nada:
 * una fecha en otro idioma no rompe una petición, solo se ve mal hasta que
 * alguien la lee.
 *
 * Estas pruebas son el candado. La primera fija el valor; la segunda y la
 * tercera fijan lo que ese valor produce, que es lo que de verdad se publica:
 * sin ellas, el día que alguien vuelva a poner `env()` ahí, la configuración
 * seguiría diciendo «es» en local y el sitio volvería a salir en inglés.
 */
class IdiomaDelSitioTest extends TestCase
{
    public function test_el_idioma_es_espanol(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
    }

    /**
     * Contra el archivo, porque contra `config()` no se puede.
     *
     * En las pruebas el `.env` local sí trae `APP_LOCALE=es`, así que devolver
     * el `env()` a `config/app.php` dejaría todo en verde aquí y volvería a
     * romper producción, que es donde esa llave no existe. Lo único que
     * distingue un caso del otro es lo que está escrito en el archivo.
     */
    public function test_el_idioma_no_se_lee_del_entorno(): void
    {
        $configuracion = (string) file_get_contents(config_path('app.php'));

        foreach (['APP_LOCALE', 'APP_FALLBACK_LOCALE'] as $llave) {
            $this->assertStringNotContainsString(
                "env('{$llave}'",
                $configuracion,
                "El idioma del sitio volvió a depender de {$llave}. Un servidor con el .env incompleto lo publica en inglés.",
            );
        }
    }

    public function test_las_fechas_se_escriben_en_espanol(): void
    {
        $fecha = CarbonImmutable::parse('2026-09-20');

        $this->assertSame(
            'domingo 20 de septiembre de 2026',
            $fecha->translatedFormat('l j \d\e F \d\e Y'),
        );
    }

    /**
     * Contra la página, y no solo contra la configuración: es donde se vio el
     * problema, y la que le contesta a quien entra al sitio.
     */
    public function test_la_pagina_de_vigilancia_publica_la_fecha_en_espanol(): void
    {
        $respuesta = $this->get(route('vigilancia'))->assertOk();

        foreach (['September', 'August', 'Sunday', 'Monday'] as $enIngles) {
            $respuesta->assertDontSee($enIngles, escape: false);
        }
    }
}
