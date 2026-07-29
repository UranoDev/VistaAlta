<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->colors([
                // Tinta de folio y rojo Sello, los del sistema visual Recibo
                // (resources/css/app.css). Sello queda donde le corresponde: en
                // lo destructivo, nunca de adorno.
                'primary' => Color::hex('#1e4d3b'),
                'danger' => Color::hex('#a22e2e'),
            ])
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
