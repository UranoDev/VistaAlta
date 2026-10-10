<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\Calle;
use App\Enums\SituacionDelLote;
use App\Mail\EntregaDeDatos;
use App\Models\Comentario;
use App\Models\Otp;
use App\Models\RegistroDePropietario;
use App\Models\SolicitudDeInternet;
use App\Models\User;
use App\Support\Datos\ArchivoDeEntrega;
use App\Support\Datos\ReunirDatosDeUnaPersona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use ZipArchive;

/**
 * `datos:entregar`: la respuesta a una solicitud de acceso a los datos (derechos
 * ARCO). Lo que más se cuida aquí no es que entregue, sino que **no entregue lo
 * de otra persona**.
 */
class EntregarDatosDeUnaPersonaTest extends TestCase
{
    use RefreshDatabase;

    private const CORREO = 'marta@correo.test';

    private const CELULAR = '5511112222';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        File::deleteDirectory(storage_path('app/entregas'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/entregas'));

        parent::tearDown();
    }

    /**
     * Marta, con todo lo que el sitio puede tener de una persona, verificado.
     */
    private function marta(): RegistroDePropietario
    {
        $registro = RegistroDePropietario::factory()
            ->verificado()
            ->conLote(Calle::Margarita, '128', SituacionDelLote::CasaTerminada)
            ->conContactoAdicional('Luis Ejemplo')
            ->create([
                'nombre' => 'Marta Ejemplo',
                'correo' => self::CORREO,
                'telefono' => self::CELULAR,
                'emergencia_nombre' => 'Ana Emergencia',
                'emergencia_telefono' => '5533334444',
            ]);

        SolicitudDeInternet::factory()->create(['celular' => self::CELULAR, 'calle' => Calle::Nube, 'numero_oficial' => '45']);
        Comentario::crearPrivado(['telefono' => self::CELULAR, 'nombre' => 'Marta', 'comentario' => 'Un comentario de Marta', 'url' => 'https://x.test']);
        Otp::create(['telefono' => self::CELULAR, 'proposito' => 'comentario', 'codigo_hash' => 'secreto-del-mecanismo', 'expira_en' => now()]);

        return $registro;
    }

    /**
     * Otra persona, con datos de todo tipo, que no deben salir en la entrega de Marta.
     */
    private function otraPersona(): void
    {
        RegistroDePropietario::factory()->verificado()->conLote(Calle::Geranio, '999')->create([
            'nombre' => 'Otra Persona Ajena',
            'correo' => 'ajena@correo.test',
            'telefono' => '5599990000',
        ]);
        SolicitudDeInternet::factory()->create(['celular' => '5599990000', 'numero_oficial' => '777']);
        Comentario::crearPublico(['telefono' => '5599990000', 'nombre' => 'Ajena', 'comentario' => 'Comentario de la ajena', 'url' => 'https://x.test']);
    }

    public function test_reune_lo_de_la_persona_por_su_correo_y_nada_de_otra(): void
    {
        $this->marta();
        $this->otraPersona();

        $persona = app(ReunirDatosDeUnaPersona::class)->para(self::CORREO);

        $this->assertSame('correo', $persona->tipo);
        $this->assertSame(1, count($persona->fuentes['registros_de_propietario']['filas']));
        $this->assertSame(1, count($persona->fuentes['lista_de_espera_de_internet']['filas']));
        $this->assertSame(1, count($persona->fuentes['comentarios']['filas']));
        $this->assertSame(1, count($persona->fuentes['codigos_de_verificacion']['filas']));

        $json = app(ArchivoDeEntrega::class)->json($persona);

        foreach (['Otra Persona Ajena', 'ajena@correo.test', '5599990000', 'Comentario de la ajena', '777'] as $ajeno) {
            $this->assertStringNotContainsString($ajeno, $json);
        }

        foreach (['Marta Ejemplo', 'Un comentario de Marta', 'Luis Ejemplo', 'Ana Emergencia', 'Margarita'] as $propio) {
            $this->assertStringContainsString($propio, $json);
        }
    }

    public function test_por_celular_tambien_reune_lo_que_se_verifico_junto(): void
    {
        $this->marta();

        $persona = app(ReunirDatosDeUnaPersona::class)->para('55 1111-2222');

        $this->assertSame('celular', $persona->tipo);
        $this->assertSame(self::CELULAR, $persona->valor);
        $this->assertContains(self::CORREO, $persona->correos);
        $this->assertSame(self::CORREO, $persona->correoDeEntrega());
    }

    /**
     * El abuso que esta regla cierra: alguien se registra con el celular de otra
     * persona y con su propio correo. Verifica su correo (puede) pero no el celular
     * (no puede). Al pedir «mis datos» por su correo, no debe llevarse lo del celular
     * ajeno, y al pedirlos por el celular no se le puede mandar a ese correo.
     */
    public function test_un_correo_no_arrastra_un_celular_que_no_verifico(): void
    {
        SolicitudDeInternet::factory()->create(['celular' => '5588887777', 'numero_oficial' => '321']);
        Comentario::crearPrivado(['telefono' => '5588887777', 'nombre' => 'Victima', 'comentario' => 'Comentario de la víctima', 'url' => 'https://x.test']);
        RegistroDePropietario::factory()->conCorreoVerificado()->create([
            'nombre' => 'Atacante',
            'correo' => 'atacante@correo.test',
            'telefono' => '5588887777',
        ]);

        $porCorreo = app(ReunirDatosDeUnaPersona::class)->para('atacante@correo.test');

        $this->assertSame([], $porCorreo->telefonos);
        $this->assertSame([], $porCorreo->fuentes['comentarios']['filas']);
        $this->assertSame([], $porCorreo->fuentes['lista_de_espera_de_internet']['filas']);

        $porCelular = app(ReunirDatosDeUnaPersona::class)->para('5588887777');

        $this->assertNull($porCelular->correoDeEntrega(), 'No se manda al correo que escribió otra persona.');
        $this->assertCount(1, $porCelular->fuentes['comentarios']['filas']);
    }

    public function test_quien_aparece_como_contacto_de_otra_persona_recibe_lo_suyo_sin_saber_quien_lo_registro(): void
    {
        RegistroDePropietario::factory()
            ->verificado()
            ->conLote(Calle::Clavel, '12')
            ->create(['nombre' => 'Quien Registro', 'emergencia_nombre' => 'Ana Contacto', 'emergencia_telefono' => '5522223333']);
        RegistroDePropietario::query()->first()->contactos()->create(['nombre' => 'Ana Contacto', 'telefono' => '5522223333', 'correo' => 'ana@correo.test']);

        $persona = app(ReunirDatosDeUnaPersona::class)->para('ana@correo.test');

        $filas = $persona->fuentes['aparece_como_contacto']['filas'];
        $this->assertCount(1, $filas);
        $this->assertSame('Ana Contacto', $filas[0]['nombre']);
        $this->assertSame([], $persona->fuentes['registros_de_propietario']['filas']);

        $json = app(ArchivoDeEntrega::class)->json($persona);
        $this->assertStringNotContainsString('Quien Registro', $json);
        $this->assertStringNotContainsString('Clavel', $json);
    }

    public function test_no_entrega_el_nombre_de_quien_valido_ni_el_codigo_del_mecanismo(): void
    {
        $this->marta();
        RegistroDePropietario::query()->first()->validar(
            User::factory()->create(['name' => 'Persona De Administracion']),
            'Revisé la escritura',
        );

        $json = app(ArchivoDeEntrega::class)->json(app(ReunirDatosDeUnaPersona::class)->para(self::CORREO));

        $this->assertStringContainsString('Revisé la escritura', $json);
        $this->assertStringNotContainsString('Persona De Administracion', $json);
        $this->assertStringNotContainsString('secreto-del-mecanismo', $json);
        $this->assertStringNotContainsString('hash', $json);
    }

    public function test_el_comando_manda_el_zip_al_correo_de_la_persona_y_no_deja_copia(): void
    {
        $this->marta();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO])
            ->expectsOutputToContain('Registro de propietario: 1')
            ->expectsOutputToContain('Se mandó a '.self::CORREO)
            ->assertSuccessful();

        Mail::assertSent(EntregaDeDatos::class, function (EntregaDeDatos $correo): bool {
            return $correo->hasTo(self::CORREO) && count($correo->attachments()) === 1 && $correo->conClave === false;
        });

        $this->assertSame([], glob(storage_path('app/entregas/*.zip')) ?: [], 'No debe quedar una copia de los datos.');
    }

    public function test_por_celular_se_manda_al_correo_verificado_junto_con_el_celular(): void
    {
        $this->marta();

        $this->artisan('datos:entregar', ['identificador' => self::CELULAR])->assertSuccessful();

        Mail::assertSent(EntregaDeDatos::class, fn (EntregaDeDatos $correo) => $correo->hasTo(self::CORREO));
    }

    public function test_por_celular_sin_correo_verificado_pide_indicar_a_donde_y_no_adivina(): void
    {
        SolicitudDeInternet::factory()->create(['celular' => '5577776666']);

        $this->artisan('datos:entregar', ['identificador' => '5577776666'])
            ->expectsOutputToContain('Indica uno con --a=')
            ->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_con_a_el_operador_decide_a_donde_se_manda(): void
    {
        SolicitudDeInternet::factory()->create(['celular' => '5577776666']);

        $this->artisan('datos:entregar', ['identificador' => '5577776666', '--a' => 'persona@correo.test'])->assertSuccessful();

        Mail::assertSent(EntregaDeDatos::class, fn (EntregaDeDatos $correo) => $correo->hasTo('persona@correo.test'));
    }

    public function test_un_correo_de_destino_invalido_no_manda_nada(): void
    {
        $this->marta();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO, '--a' => 'esto-no-es-un-correo'])->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_si_no_hay_datos_de_la_persona_no_genera_ni_manda_nada(): void
    {
        $this->artisan('datos:entregar', ['identificador' => 'nadie@correo.test'])
            ->expectsOutputToContain('No hay datos de esa persona')
            ->assertFailed();

        Mail::assertNothingSent();
        $this->assertSame([], glob(storage_path('app/entregas/*.zip')) ?: []);
    }

    public function test_rechaza_un_identificador_que_no_es_correo_ni_celular(): void
    {
        $this->artisan('datos:entregar', ['identificador' => '12345'])->assertFailed();
        $this->artisan('datos:entregar', ['identificador' => 'sin-arroba'])->assertFailed();
        $this->artisan('datos:entregar', ['identificador' => 'mal@'])->assertFailed();

        Mail::assertNothingSent();
    }

    public function test_sin_enviar_deja_el_zip_con_el_txt_y_el_json(): void
    {
        $this->marta();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO, '--sin-enviar' => true])
            ->expectsOutputToContain('bórralo cuando termines')
            ->assertSuccessful();

        Mail::assertNothingSent();

        $zips = glob(storage_path('app/entregas/*.zip')) ?: [];
        $this->assertCount(1, $zips);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zips[0]));
        $this->assertEqualsCanonicalizing(['datos.json', 'datos.txt'], [$zip->getNameIndex(0), $zip->getNameIndex(1)]);

        $json = json_decode((string) $zip->getFromName('datos.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('correo', $json['identificado_por']['tipo']);
        $this->assertSame(self::CORREO, $json['identificado_por']['valor']);
        $this->assertSame('Marta Ejemplo', $json['datos']['registros_de_propietario'][0]['nombre']);
        $this->assertSame('Margarita', $json['datos']['registros_de_propietario'][0]['lotes'][0]['calle']);
        $this->assertSame([], $json['pagos']['filas']);

        $txt = (string) $zip->getFromName('datos.txt');
        $zip->close();

        $this->assertStringContainsString('DATOS PERSONALES EN VISTA ALTA', $txt);
        $this->assertStringContainsString('Nombre: Marta Ejemplo', $txt);
        $this->assertStringContainsString('Folio: INT-', $txt);
        $this->assertStringContainsString('== Pagos ==', $txt);
        $this->assertStringContainsString('todavía no registra pagos', $txt);
        $this->assertMatchesRegularExpression('/Registrado el: \d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $txt);
    }

    public function test_con_clave_el_zip_no_se_abre_sin_ella_y_el_correo_lo_avisa(): void
    {
        if (! method_exists(ZipArchive::class, 'setEncryptionName')) {
            $this->markTestSkipped('Este PHP no puede cifrar ZIP.');
        }

        $this->marta();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO, '--sin-enviar' => true, '--clave' => 'clave-de-prueba-larga'])
            ->assertSuccessful();

        $zips = glob(storage_path('app/entregas/*.zip')) ?: [];
        $zip = new ZipArchive;
        $zip->open($zips[0]);

        $this->assertFalse($zip->getFromName('datos.json'), 'Sin la clave no debe poder leerse.');

        $zip->setPassword('clave-de-prueba-larga');
        $this->assertStringContainsString('Marta Ejemplo', (string) $zip->getFromName('datos.json'));
        $zip->close();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO, '--clave' => 'clave-de-prueba-larga'])
            ->expectsOutputToContain('dásela a la persona por otro medio')
            ->assertSuccessful();

        Mail::assertSent(EntregaDeDatos::class, fn (EntregaDeDatos $correo) => $correo->conClave === true);
    }

    public function test_el_correo_dice_que_la_clave_va_por_otro_medio_y_no_la_trae(): void
    {
        $html = (new EntregaDeDatos('/tmp/no-importa.zip', conClave: true))->render();

        $this->assertStringContainsString('protegido con una clave', $html);
        $this->assertStringContainsString('te la damos por otro medio', $html);
        $this->assertStringNotContainsString('clave-de-prueba', $html);
    }

    public function test_deja_constancia_en_el_log_sin_poner_los_datos_de_la_persona(): void
    {
        $this->marta();
        Log::spy();

        $this->artisan('datos:entregar', ['identificador' => self::CORREO])->assertSuccessful();

        Log::shouldHaveReceived('info')->withArgs(function (string $mensaje, array $contexto = []): bool {
            return str_starts_with($mensaje, 'datos:entregar')
                && ! str_contains(json_encode($contexto), 'marta')
                && ! str_contains(json_encode($contexto), self::CELULAR)
                && isset($contexto['identificador'], $contexto['renglones']);
        })->once();
    }
}
