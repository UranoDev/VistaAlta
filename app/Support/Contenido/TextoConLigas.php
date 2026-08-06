<?php

declare(strict_types=1);

namespace App\Support\Contenido;

use Illuminate\Support\HtmlString;

/**
 * Texto capturado desde el panel —la descripción de una Actividad, el detalle de
 * un Pendiente— con **una sola** cosa además de texto plano: ligas internas.
 *
 * Existe porque la Bitácora necesitaba mandar al lector a otra página del sitio
 * con una frase, no con una URL a pelo pegada a media línea. Y se quedó en eso:
 * no hay negritas, ni títulos, ni listas, ni imágenes. Esto **no es meterle
 * Markdown a la Bitácora**; es una liga, y ahí se acaba.
 *
 * ## Por qué solo rutas internas
 *
 * El destino tiene que empezar con `/` y casar contra `RUTA_INTERNA`. Con eso no
 * hay esquema que se pueda colar —ni `javascript:`, ni `data:`, ni un `http://`
 * a un dominio que alguien registró parecido al nuestro— y la Bitácora no se
 * vuelve un lugar desde donde mandar vecinos fuera del sitio. El día que haga
 * falta enlazar hacia afuera, esa es una decisión aparte y con sus propios
 * cuidados, no un `|` más en esta expresión.
 *
 * ## El orden importa y no es negociable
 *
 * Se **escapa primero** y se sustituye después. Al revés, un `<script>` escrito
 * en el panel saldría vivo: el escape posterior se comería las etiquetas que
 * este método acaba de generar, o —peor— se dejaría pasar lo que venía en el
 * texto. Escapar primero significa que para cuando se busca el patrón ya no
 * queda HTML del autor, solo entidades.
 *
 * Los corchetes y paréntesis sobreviven al escape de `e()`, así que el patrón se
 * sigue encontrando igual.
 */
final class TextoConLigas
{
    /**
     * Rutas internas absolutas y nada más: `/vigilancia`, `/reporte-financiero/2026-06/detalle`.
     */
    private const RUTA_INTERNA = '/\[([^\]\n]+)\]\((\/[a-z0-9\-\/]*)\)/i';

    public static function aHtml(?string $texto): HtmlString
    {
        $escapado = e((string) $texto);

        $conLigas = preg_replace_callback(
            self::RUTA_INTERNA,
            static fn (array $coincidencia): string => sprintf(
                '<a href="%s" class="font-medium text-tinta underline underline-offset-2 hover:text-tinta-suave">%s</a>',
                $coincidencia[2],
                $coincidencia[1],
            ),
            $escapado,
        );

        return new HtmlString($conLigas ?? $escapado);
    }
}
