<?php

declare(strict_types=1);

namespace Tests\Feature\Contenido;

use App\Support\Contenido\TextoConLigas;
use Tests\TestCase;

/**
 * El texto de la Bitácora y de «Lo que sigue», con ligas internas y nada más.
 *
 * Esta clase es lo único que separa «una Actividad puede enlazar a otra página»
 * de «lo que se capture en el panel se pinta como HTML». Las pruebas que
 * importan no son las que comprueban que la liga sale bien: son las que
 * comprueban lo que **no** sale.
 */
class TextoConLigasTest extends TestCase
{
    public function test_convierte_una_ruta_interna_en_liga(): void
    {
        $html = (string) TextoConLigas::aHtml('Se consulta en [la página de Vigilancia](/vigilancia).');

        $this->assertStringContainsString('href="/vigilancia"', $html);
        $this->assertStringContainsString('>la página de Vigilancia</a>', $html);
    }

    /**
     * El orden —escapar primero, sustituir después— es lo que sostiene esto. Al
     * revés, el escape posterior se comería las etiquetas recién generadas o
     * dejaría pasar las del autor.
     */
    public function test_el_html_capturado_sigue_saliendo_escapado(): void
    {
        $html = (string) TextoConLigas::aHtml('<script>alert(1)</script> y <b>negritas</b>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * Solo rutas internas absolutas. Sin esto la Bitácora sería un lugar desde
     * donde mandar vecinos a donde sea, y un `javascript:` con texto amable es
     * exactamente lo que nadie revisa al capturar una actividad.
     */
    public function test_no_convierte_destinos_que_no_sean_rutas_internas(): void
    {
        foreach ([
            '[ir](https://ejemplo.com)',
            '[ir](http://ejemplo.com)',
            '[ir](javascript:alert(1))',
            '[ir](data:text/html;base64,PHNjcmlwdD4=)',
            '[ir](mailto:alguien@ejemplo.com)',
            '[ir](//ejemplo.com)',
        ] as $intento) {
            $html = (string) TextoConLigas::aHtml($intento);

            $this->assertStringNotContainsString('<a ', $html, "No debería enlazarse: {$intento}");
        }
    }

    public function test_el_texto_sin_ligas_pasa_igual_y_conserva_los_saltos(): void
    {
        $texto = "Primer párrafo.\n\nSegundo párrafo con $32,400 y comillas «así».";

        $this->assertSame(e($texto), (string) TextoConLigas::aHtml($texto));
    }

    public function test_tolera_nulo(): void
    {
        $this->assertSame('', (string) TextoConLigas::aHtml(null));
    }
}
