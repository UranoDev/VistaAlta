<?php

use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\ConvivenciaController;
use App\Http\Controllers\PropuestaController;
use App\Http\Controllers\ReporteFinancieroController;
use App\Http\Controllers\VigilanciaController;
use Illuminate\Support\Facades\Route;

/*
 * La raíz ya no sirve la Propuesta: manda al Reporte financiero (URVA-95). Lo
 * primero que se le pone enfrente a la Asamblea es la cuenta del mes.
 *
 * Va con **301 y no con un alias** que sirva la misma página en las dos
 * direcciones. La dirección de la raíz anduvo circulando entre los Colonos, así
 * que quien la tenga guardada tiene que terminar viendo la dirección buena en
 * la barra —no una copia de la página bajo otra URL—, y los buscadores tienen
 * que consolidar en `/reporte-financiero` en vez de repartir entre dos
 * direcciones lo que vale una. El `<link rel="canonical">` del Reporte
 * financiero resuelve el otro empate, el del mes vigente contra su URL con
 * fecha; éste no lo necesita, porque la raíz no llega a pintar nada.
 */
Route::permanentRedirect('/', '/reporte-financiero');

/*
 * Las páginas públicas del sitio. Todas de lectura y sin autenticación: el
 * único lugar que la pide es el panel de la Mesa Directiva.
 *
 * El orden de abajo es el del menú (`encabezado.blade.php`), y ya no es el de
 * la rendición de cuentas: la Propuesta bajó de primera a penúltima el día que
 * dejó la raíz. Demanda conserva el final porque no respalda nada —pide algo, y
 * entrar por ahí dejaría la petición antes que el asunto—; es estática, así que
 * no lleva controlador.
 *
 * Vigilancia sí lo lleva aunque tampoco toque la base: no es estática, contesta
 * según el reloj del acceso, y a las 22:00 dice algo distinto que a las 21:59.
 *
 * Administración entró después de Vigilancia y hace juego con ella —una dice
 * quién cuida el acceso y la otra quiénes ocupan los cargos—, así que va a su
 * lado y no al final. También lleva controlador, por otra razón: no cambia con
 * el reloj, pero arma los siete integrantes desde `config/contenido.php` y ese
 * armado no va dentro del blade.
 *
 * Convivencia entró tercera (URVA-97) y es la única de las seis que además
 * sirve páginas debajo de sí — un post por dirección, más abajo en este archivo.
 */
Route::get('/reporte-financiero', [ReporteFinancieroController::class, 'index'])->name('reporte-financiero');
Route::get('/actividades', [ActividadesController::class, 'index'])->name('actividades');
Route::get('/convivencia', [ConvivenciaController::class, 'index'])->name('convivencia');
Route::get('/vigilancia', [VigilanciaController::class, 'index'])->name('vigilancia');
Route::get('/administracion', [AdministracionController::class, 'index'])->name('administracion');
Route::get('/propuesta', [PropuestaController::class, 'index'])->name('propuesta');
Route::view('/demanda', 'pages.demanda')->name('demanda');

/*
 * Cada mes ya rendido conserva su propia dirección, para que la rendición de
 * cuentas se pueda consultar hacia atrás (docs/adr/0005). La restricción a
 * `AAAA-MM` no es cosmética: sin ella, el parámetro se tragaría cualquier ruta
 * hermana que se agregue después bajo `/reporte-financiero/`.
 */
Route::get('/reporte-financiero/{mes}', [ReporteFinancieroController::class, 'mes'])
    ->where('mes', '[0-9]{4}-[0-9]{2}')
    ->name('reporte-financiero.mes');

/*
 * Cada post de Convivencia en su propia dirección, para que se pueda pegar el
 * enlace de uno solo en el grupo de vecinos. El post se resuelve por `slug` y
 * no por `id`: la dirección dice de qué se trata antes de abrirla.
 *
 * El campo del binding se declara aquí —`{post:slug}`— y no con un
 * `getRouteKeyName()` en el modelo, que lo volvería la llave también de las
 * rutas del panel: la pantalla de edición quedaría colgando del campo que esa
 * pantalla existe para cambiar.
 *
 * La restricción del parámetro es del mismo tipo que la de `{mes}` de arriba y
 * está por lo mismo: sin ella, `{post}` se tragaría cualquier ruta hermana que
 * se agregue después bajo `/convivencia/`. El patrón es el de un slug —minúsculas,
 * dígitos y guiones—, que es lo que el panel valida al capturarlo.
 */
Route::get('/convivencia/{post:slug}', [ConvivenciaController::class, 'post'])
    ->where('post', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('convivencia.post');

/*
 * Dejar un Comentario sobre la Propuesta: primero el OTP que valida el
 * teléfono, luego el comentario. Las cuatro rechazan si la Recepción de
 * comentarios está cerrada, y también si la Vía de recepción está en WhatsApp
 * —el sitio deja de ser por donde se reciben, y son rutas públicas: esconder el
 * formulario no las apaga—.
 */
Route::post('/comentarios/codigo', [PropuestaController::class, 'enviarOtp'])->name('comentarios.codigo');
Route::post('/comentarios/validar', [PropuestaController::class, 'verificarOtp'])->name('comentarios.validar');
Route::post('/comentarios/cambiar-telefono', [PropuestaController::class, 'cambiarTelefono'])->name('comentarios.cambiar-telefono');
Route::post('/comentarios', [PropuestaController::class, 'store'])->name('comentarios.store');

/*
 * Las dos páginas legales. Estáticas, así que tampoco llevan controlador.
 * Conservan las URLs que tenían en nvavista —que son además las que el propio
 * Aviso cita en su sección 8— y se enlazan desde el pie, no desde el menú: ahí
 * arriba va lo que se le pide a la Asamblea que lea.
 */
Route::view('/aviso-de-privacidad', 'pages.privacidad')->name('privacidad');
Route::view('/terminos-de-servicio', 'pages.terminos')->name('terminos');

/*
 * Referencia del sistema visual "Palette Receipt". Es una herramienta de construcción, no
 * una página del sitio: no se sirve en producción.
 */
if (! app()->isProduction()) {
    Route::view('/sistema-visual', 'pages.sistema-visual')->name('sistema-visual');
}
