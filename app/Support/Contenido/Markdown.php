<?php

declare(strict_types=1);

namespace App\Support\Contenido;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * El contenido de un post de Convivencia, de Markdown a HTML.
 *
 * Es lo contrario de `TextoConLigas`, y las dos existen a la vez a propósito.
 * Aquél es texto plano con **una sola** concesión —una liga interna— para lo
 * que se captura en Actividades y Pendientes, que son renglones de una lista.
 * Esto es un documento: lleva títulos, listas, citas, tablas e imágenes, porque
 * un post se lee como una página y no como una entrada de bitácora.
 *
 * ## Las dos rejas, y por qué son dos
 *
 * 1. **CommonMark no deja pasar HTML del autor.** `html_input => strip` tira
 *    cualquier etiqueta escrita a mano dentro del Markdown, y
 *    `allow_unsafe_links => false` descarta los esquemas `javascript:`,
 *    `data:` y `vbscript:` en ligas e imágenes. Los dos hay que decirlos: los
 *    valores de fábrica de la librería son `allow` y `true`.
 * 2. **El HTML que sale se sanea igual.** `sanitizeHtml()` —el saneador de
 *    Symfony que ya monta Filament— vuelve a pasar sobre el resultado. No es
 *    redundancia por gusto: la primera reja depende de la configuración de un
 *    paquete que se actualiza solo, y esto se pinta con `{!! !!}` en una página
 *    pública. Si un día la primera se afloja, la segunda sigue puesta.
 *
 * El orden es al revés que en `TextoConLigas` —allá se escapa *antes* de buscar
 * el patrón— y tiene que serlo: aquí el HTML lo genera este método, así que
 * sanear antes no tendría nada que sanear y sanear después es lo único que
 * revisa lo que de veras va a salir.
 */
final class Markdown
{
    public static function aHtml(?string $markdown): HtmlString
    {
        $html = Str::markdown((string) $markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return new HtmlString(Str::sanitizeHtml($html));
    }

    /**
     * El arranque del post en texto plano, para el resumen del índice y para el
     * renglón que WhatsApp pinta debajo del título cuando alguien pega la liga.
     *
     * Sale del mismo contenido y no de un campo «extracto» aparte: un resumen
     * que se captura por separado es un resumen que se queda hablando del
     * borrador anterior en cuanto alguien edita el post.
     */
    public static function aResumen(?string $markdown, int $caracteres = 180): string
    {
        // Del HTML ya saneado y no del Markdown crudo: así los `##`, los `*` y
        // las ligas no salen con su sintaxis a la vista, y una imagen al inicio
        // del post no aporta ni un carácter al resumen en vez de aportar su
        // `![alt](url)` entero.
        $texto = html_entity_decode(
            strip_tags(self::aHtml($markdown)->toHtml()),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $texto) ?? ''), $caracteres);
    }
}
