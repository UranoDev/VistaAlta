<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Models\User;
use App\Support\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La versión desplegada que se muestra junto al nombre del panel. Sale del
 * CHANGELOG, que `release.ps1` escribe en el mismo commit que etiqueta.
 */
class VersionEnElPanelTest extends TestCase
{
    use RefreshDatabase;

    private function changelog(string $contenido): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'changelog');
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    public function test_lee_el_encabezado_mas_reciente(): void
    {
        $ruta = $this->changelog("# Changelog\n\n## [2026.10.06] - 2026 oct 06\n### Features\n\n## [2026.09.29.1] - 2026 sep 29\n");

        $this->assertSame('2026.10.06', Version::actual($ruta));
    }

    public function test_lo_que_sigue_sin_cortar_no_es_una_version(): void
    {
        $ruta = $this->changelog("# Changelog\n\n## [Unreleased]\n- algo\n\n## [2026.09.29] - 2026 sep 29\n");

        $this->assertNull(Version::actual($ruta));
    }

    public function test_sin_changelog_o_sin_encabezados_no_hay_version(): void
    {
        $this->assertNull(Version::actual(sys_get_temp_dir().'/no-existe-'.uniqid().'.md'));
        $this->assertNull(Version::actual($this->changelog("# Changelog\n")));
    }

    public function test_el_changelog_del_proyecto_trae_una_version_valida(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}\.\d{2}\.\d{2}(\.\d+)?$/', (string) Version::actual());
    }

    public function test_el_panel_muestra_la_version_junto_al_nombre(): void
    {
        $this->actingAs(User::factory()->create());

        $html = $this->get('/admin/actividades')->assertOk()->getContent();

        // Dos copias a propósito: la de la barra superior (escritorio) y la del
        // menú lateral (celular). En cada ancho el CSS deja ver una sola.
        $this->assertSame(2, preg_match_all('/v'.preg_quote((string) Version::actual(), '/').'\s*<\/span>/', $html));
    }
}
