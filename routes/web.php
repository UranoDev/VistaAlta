<?php

use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\PropuestaController;
use App\Http\Controllers\ReporteFinancieroController;
use Illuminate\Support\Facades\Route;

/*
 * Las páginas públicas del sitio. Todas de lectura y sin autenticación: el
 * único lugar que la pide es el panel de la Mesa Directiva.
 *
 * Las tres primeras son la rendición de cuentas —la Propuesta y lo que la
 * respalda—. La cuarta no respalda nada: pide algo, y por eso va aparte y al
 * final. Es estática, así que no lleva controlador.
 */
Route::get('/', [PropuestaController::class, 'index'])->name('propuesta');
Route::get('/actividades', [ActividadesController::class, 'index'])->name('actividades');
Route::get('/reporte-financiero', [ReporteFinancieroController::class, 'index'])->name('reporte-financiero');
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
 * Dejar un Comentario sobre la Propuesta: primero el OTP que valida el
 * teléfono, luego el comentario. Las tres rechazan si la Recepción de
 * comentarios está cerrada.
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
 * Referencia del sistema visual "Recibo". Es una herramienta de construcción, no
 * una página del sitio: no se sirve en producción.
 */
if (! app()->isProduction()) {
    Route::view('/sistema-visual', 'pages.sistema-visual')->name('sistema-visual');
}
