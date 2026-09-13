<?php

declare(strict_types=1);

namespace Tests\Feature\Contenido;

use App\Support\Contenido\Markdown;
use Tests\TestCase;

/**
 * El contenido de un post de Convivencia, de Markdown a HTML.
 *
 * La mitad de estas pruebas es de formato —que el post se lea con títulos,
 * listas e imágenes, que es lo que lo distingue de una Actividad— y la otra
 * mitad es de las dos rejas. Esas segundas importan más de lo que parece: el
 * resultado se pinta con `{!! !!}` en una página pública, así que lo único que
 * separa el texto capturado en el panel de un `<script>` corriendo en el
 * navegador de un Colono es esta clase.
 */
class MarkdownTest extends TestCase
{
    public function test_el_markdown_sale_con_su_formato(): void
    {
        $html = Markdown::aHtml("## Manejo de la basura\n\nUn párrafo con **énfasis**.\n\n- uno\n- dos\n")->toHtml();

        $this->assertStringContainsString('<h2>Manejo de la basura</h2>', $html);
        $this->assertStringContainsString('<strong>énfasis</strong>', $html);
        $this->assertStringContainsString('<li>uno</li>', $html);
    }

    /**
     * Las imágenes son la razón de que el contenido sea Markdown y no texto
     * plano: se suben desde el editor del panel y quedan insertadas en el texto.
     */
    public function test_la_imagen_insertada_por_el_editor_sobrevive(): void
    {
        $html = Markdown::aHtml('![Los contenedores](/storage/convivencia/basura.png)')->toHtml();

        $this->assertStringContainsString('src="/storage/convivencia/basura.png"', $html);
        $this->assertStringContainsString('alt="Los contenedores"', $html);
    }

    public function test_la_cita_y_la_tabla_tambien_se_pintan(): void
    {
        $html = Markdown::aHtml("> Una cita\n\n| Día | Hora |\n| --- | --- |\n| Lunes | 07:00 |\n")->toHtml();

        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('<th>Día</th>', $html);
        $this->assertStringContainsString('<td>Lunes</td>', $html);
    }

    /**
     * El HTML escrito a mano dentro del Markdown no pasa. Es la primera reja
     * —`html_input => strip`— y hay que decirla: el valor de fábrica de
     * CommonMark es `allow`.
     */
    public function test_el_html_escrito_en_el_panel_no_sale_vivo(): void
    {
        $html = Markdown::aHtml("Texto normal.\n\n<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>")->toHtml();

        $this->assertStringContainsString('Texto normal.', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    /**
     * Una liga de Markdown con esquema peligroso conserva el texto pero pierde
     * el destino. Se afirma que el texto sigue ahí a propósito: si la clase
     * tirara el párrafo entero, la prueba del `href` pasaría igual y estaríamos
     * midiendo otra cosa.
     */
    public function test_la_liga_con_esquema_peligroso_se_queda_sin_destino(): void
    {
        $html = Markdown::aHtml('[Da clic aquí](javascript:alert(1))')->toHtml();

        $this->assertStringContainsString('Da clic aquí', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    /**
     * El resumen sale del HTML ya armado y no del Markdown crudo: si saliera del
     * crudo, el índice mostraría los `##` y los `**` a la vista, y una imagen al
     * inicio del post se comería el resumen con su `![alt](url)`.
     */
    public function test_el_resumen_no_arrastra_la_sintaxis_del_markdown(): void
    {
        $resumen = Markdown::aResumen("![Los contenedores](/storage/convivencia/basura.png)\n\n## Manejo de la basura\n\nLa basura **se saca** los martes.");

        $this->assertStringNotContainsString('#', $resumen);
        $this->assertStringNotContainsString('**', $resumen);
        $this->assertStringNotContainsString('/storage/', $resumen);
        $this->assertStringContainsString('La basura se saca los martes.', $resumen);
    }

    public function test_el_resumen_se_corta_al_largo_pedido(): void
    {
        $resumen = Markdown::aResumen(str_repeat('palabra ', 200), caracteres: 40);

        // `Str::limit` agrega los puntos suspensivos por encima del corte.
        $this->assertLessThanOrEqual(43, mb_strlen($resumen));
        $this->assertStringEndsWith('...', $resumen);
    }

    public function test_un_post_sin_contenido_no_truena(): void
    {
        $this->assertSame('', trim(Markdown::aHtml(null)->toHtml()));
        $this->assertSame('', Markdown::aResumen(null));
    }
}
