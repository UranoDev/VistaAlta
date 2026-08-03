<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * El panel de la Mesa Directiva, en `/admin`. Es el único lugar del sitio que
 * pide autenticación: las páginas públicas no piden nada nunca, y por eso
 * el panel monta su propio grupo de middleware en vez de tocar el de `web`.
 *
 * No hay Dashboard: a lo que se entra es a la Cola de moderación —la pestaña de
 * entrada de la pantalla de Comentarios—, así que ésa es la portada del panel en
 * vez de una pantalla de widgets que nadie mira.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Mesa Directiva · Vista Alta')
            ->favicon(asset('favicon.ico'))
            /*
             * El panel se viste con Palette Receipt para que quien administra vea
             * la misma hoja que la Asamblea lee. Los neutros y las superficies van
             * en el tema (resources/css/filament/admin/theme.css), que es donde se
             * explica por qué no caben aquí.
             */
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                // Tinta de folio, rojo Sello y Menta, los de Palette Receipt
                // (resources/css/palette-receipt.css). Sello queda donde le
                // corresponde: en lo destructivo, nunca de adorno.
                //
                // De estas semillas Filament solo aprovecha el tono: la rampa de
                // Tinta y la de Menta salen al doble de saturación y el tema las
                // corrige encima. Cambiar un hex aquí mueve el tono pero no la
                // intensidad — la receta para regenerarlas está en el tema.
                'primary' => Color::hex('#1e4d3b'),
                'danger' => Color::hex('#a22e2e'),
                'success' => Color::hex('#bfe0ce'),
            ])
            /*
             * Palette Receipt es papel y tinta: no tiene versión nocturna, y un
             * panel oscuro dejaría a la Mesa Directiva viendo algo que no se
             * parece al sitio. Inventarle tonos sería ampliar el sistema visual,
             * no aplicarlo, así que el panel se queda en claro.
             */
            ->darkMode(false)
            /*
             * Las familias van declaradas aunque el tema reescriba la pila
             * completa: sin esto Filament da por hecho que se usa la suya y
             * precarga las Inter que el panel ya no pinta. `LocalFontProvider`
             * evita que salga a buscarlas a un CDN —las nuestras las monta el
             * render hook de abajo, desde el propio servidor—.
             */
            ->font('IBM Plex Sans', provider: LocalFontProvider::class)
            ->monoFont('IBM Plex Mono', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_START, fn (): View => view('filament.fuentes'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
