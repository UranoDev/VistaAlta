<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Comentario;
use App\Models\LoteRegistrado;
use App\Models\Otp;
use App\Models\RegistroDePropietario;
use App\Models\SolicitudDeInternet;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `datos:depurar`: lo que el Aviso de Privacidad promete en su sección 6. Los
 * datos personales que llevan más de dos años sin actualizarse se borran solos;
 * lo que se actualizó o se usó dentro del plazo se queda.
 */
class DepurarDatosCaducadosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fija `updated_at` sin que Eloquent lo pise: con `forceFill()->save()` lo
     * volvería a poner en la fecha de hoy.
     */
    private function envejecer(string $tabla, int $id, string $cuando): void
    {
        DB::table($tabla)->where('id', $id)->update(['updated_at' => $cuando]);
    }

    public function test_borra_lo_que_lleva_mas_de_dos_anos_sin_actualizarse_y_conserva_lo_demas(): void
    {
        $viejo = RegistroDePropietario::factory()->create(['nombre' => 'Registro Viejo']);
        $reciente = RegistroDePropietario::factory()->create(['nombre' => 'Registro Reciente']);
        $this->envejecer('registros_de_propietarios', $viejo->id, now()->subYears(2)->subDay()->toDateTimeString());
        $this->envejecer('registros_de_propietarios', $reciente->id, now()->subYears(2)->addDay()->toDateTimeString());

        $this->artisan('datos:depurar')->assertSuccessful();

        $this->assertModelMissing($viejo);
        $this->assertModelExists($reciente);
    }

    public function test_un_registro_borrado_se_lleva_sus_lotes_y_sus_contactos(): void
    {
        $viejo = RegistroDePropietario::factory()->conContactoAdicional('Luis Ejemplo')->create();
        $this->envejecer('registros_de_propietarios', $viejo->id, now()->subYears(3)->toDateTimeString());

        $this->assertSame(1, LoteRegistrado::query()->count());

        $this->artisan('datos:depurar')->assertSuccessful();

        $this->assertDatabaseCount('lotes_registrados', 0);
        $this->assertDatabaseCount('contactos_del_registro', 0);
    }

    public function test_depura_cada_tabla_con_datos_personales(): void
    {
        $limite = now()->subYears(3)->toDateTimeString();

        $solicitud = SolicitudDeInternet::factory()->create();
        $comentario = Comentario::factory()->create();
        $otp = Otp::create(['telefono' => '5512345678', 'proposito' => 'comentario', 'codigo_hash' => 'x', 'expira_en' => now()]);

        $this->envejecer('solicitudes_de_internet', $solicitud->id, $limite);
        $this->envejecer('comentarios', $comentario->id, $limite);
        $this->envejecer('otps', $otp->id, $limite);

        $this->artisan('datos:depurar')->assertSuccessful();

        $this->assertModelMissing($solicitud);
        $this->assertModelMissing($comentario);
        $this->assertModelMissing($otp);
    }

    public function test_actualizar_un_registro_renueva_su_plazo(): void
    {
        $registro = RegistroDePropietario::factory()->create();
        $this->envejecer('registros_de_propietarios', $registro->id, now()->subYears(3)->toDateTimeString());

        // Se usa: la Administración lo valida, y eso lo toca.
        $registro->fresh()->forceFill(['validacion_nota' => 'revisado'])->save();

        $this->artisan('datos:depurar')->assertSuccessful();

        $this->assertModelExists($registro);
    }

    public function test_simular_cuenta_pero_no_borra_nada(): void
    {
        $viejo = SolicitudDeInternet::factory()->create();
        $this->envejecer('solicitudes_de_internet', $viejo->id, now()->subYears(3)->toDateTimeString());

        $this->artisan('datos:depurar', ['--simular' => true])
            ->expectsOutputToContain('SolicitudDeInternet: 1')
            ->expectsOutputToContain('Se borrarían 1 registro(s)')
            ->assertSuccessful();

        $this->assertModelExists($viejo);
    }

    public function test_el_plazo_sale_de_la_configuracion_la_misma_que_el_aviso(): void
    {
        $registro = SolicitudDeInternet::factory()->create();
        $this->envejecer('solicitudes_de_internet', $registro->id, now()->subYears(2)->subDay()->toDateTimeString());

        config(['contenido.legal.conservacion_anos' => 3]);
        $this->artisan('datos:depurar')->assertSuccessful();
        $this->assertModelExists($registro);

        config(['contenido.legal.conservacion_anos' => 2]);
        $this->artisan('datos:depurar')->assertSuccessful();
        $this->assertModelMissing($registro);
    }

    public function test_se_niega_a_correr_con_un_plazo_invalido_para_no_borrar_de_mas(): void
    {
        $registro = SolicitudDeInternet::factory()->create();
        $this->envejecer('solicitudes_de_internet', $registro->id, now()->subYears(10)->toDateTimeString());

        config(['contenido.legal.conservacion_anos' => 0]);

        $this->artisan('datos:depurar')->assertFailed();

        $this->assertModelExists($registro);
    }

    public function test_corre_solo_todos_los_dias(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento) => str_contains((string) $evento->command, 'datos:depurar'));

        $this->assertCount(1, $eventos);
        $this->assertSame('30 3 * * *', $eventos->first()->expression);
    }
}
