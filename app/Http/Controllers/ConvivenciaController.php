<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IntroDeConvivencia;
use App\Models\Post;
use Illuminate\View\View;

/**
 * Convivencia: lo que la Mesa Directiva publica sobre cómo se vive el
 * fraccionamiento. Pública y sin autenticación, como el resto del sitio.
 *
 * Dos páginas y no una: el índice en `/convivencia` y cada post en
 * `/convivencia/{slug}`. El mismo reparto que el Reporte financiero por mes
 * (`docs/adr/0005`), y por el mismo motivo — un post se comparte por enlace en
 * un grupo de vecinos, y mandar a alguien a buscarlo dentro de una página larga
 * es perderlo.
 *
 * El índice lee la tabla en cada petición, sin caché de por medio: si un slug
 * cambia en el panel, el enlace del índice cambia con él en la siguiente carga.
 */
class ConvivenciaController extends Controller
{
    public function index(): View
    {
        return view('pages.convivencia', [
            'posts' => Post::recientes()->get(),
            'intro' => IntroDeConvivencia::texto(),
        ]);
    }

    /**
     * El post se resuelve por `slug` (ver `Post::getRouteKeyName`), así que un
     * slug que ya no existe —porque se editó desde el panel— cae en 404 solo.
     * Es el costo asumido de dejar el slug editable (URVA-96).
     */
    public function post(Post $post): View
    {
        return view('pages.convivencia-post', [
            'post' => $post,
        ]);
    }
}
