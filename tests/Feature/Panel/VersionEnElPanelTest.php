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

    private function archivo(string $contenido): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'version');
        file_put_contents($ruta, $contenido);

        return $ruta;
    }

    public function test_lee_la_version_del_archivo(): void
    {
        $this->assertSame('2026.10.06', Version::actual($this->archivo('2026.10.06
')));
        $this->assertSame('2026.10.06.1', Version::actual($this->archivo('  2026.10.06.1  
')));
    }

    public function test_lo_que_no_es_una_version_no_se_muestra(): void
    {
        $this->assertNull(Version::actual($this->archivo('Unreleased
')));
        $this->assertNull(Version::actual($this->archivo('2026.10.06 <script>
')));
        $this->assertNull(Version::actual($this->archivo('')));
    }

    public function test_sin_archivo_no_hay_version(): void
    {
        $this->assertNull(Version::actual(sys_get_temp_dir().'/no-existe-'.uniqid()));
    }

    public function test_el_proyecto_trae_su_archivo_de_version(): void
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
