<?php

declare(strict_types=1);

namespace Tests\Feature\Panel;

use App\Enums\Calle;
use App\Enums\SituacionDelLote;
use App\Filament\Resources\RegistrosDePropietarios\Pages\ListRegistrosDePropietarios;
use App\Filament\Resources\RegistrosDePropietarios\RegistrosDePropietariosResource;
use App\Filament\Resources\RegistrosDePropietarios\Widgets\ResumenDeRegistros;
use App\Models\RegistroDePropietario;
use App\Models\User;
use App\Support\Registro\ListaCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * La lista de registros de propietarios en el panel: la única pantalla donde se
 * ven sus teléfonos y correos.
 */
class RegistrosDePropietariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_la_exige_con_sesion(): void
    {
        $this->get('/admin/registros')->assertRedirect('/admin/login');
    }

    public function test_lista_los_registros_con_sus_lotes(): void
    {
        $this->actingAs(User::factory()->create());

        $registro = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128', SituacionDelLote::CasaTerminada)->create(['nombre' => 'Marta Ejemplo']);

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertCanSeeTableRecords([$registro])
            ->assertSee('Marta Ejemplo')
            ->assertSee('Margarita 128');
    }

    public function test_se_busca_por_calle_y_numero_nombre_o_telefono(): void
    {
        $this->actingAs(User::factory()->create());

        $marta = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128')->create(['nombre' => 'Marta Ejemplo', 'telefono' => '5511112222']);
        $luis = RegistroDePropietario::factory()->conLote(Calle::Nube, '45')->create(['nombre' => 'Luis Ejemplo', 'telefono' => '5533334444']);

        Livewire::test(ListRegistrosDePropietarios::class)
            ->searchTable('Margarita 128')
            ->assertCanSeeTableRecords([$marta])
            ->assertCanNotSeeTableRecords([$luis])
            ->searchTable('Luis')
            ->assertCanSeeTableRecords([$luis])
            ->assertCanNotSeeTableRecords([$marta])
            ->searchTable('5511112222')
            ->assertCanSeeTableRecords([$marta])
            ->assertCanNotSeeTableRecords([$luis]);
    }

    public function test_se_filtra_por_calle(): void
    {
        $this->actingAs(User::factory()->create());

        $marta = RegistroDePropietario::factory()->conLote(Calle::Margarita, '128')->create();
        $luis = RegistroDePropietario::factory()->conLote(Calle::Nube, '45')->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->filterTable('calle', 'Nube')
            ->assertCanSeeTableRecords([$luis])
            ->assertCanNotSeeTableRecords([$marta]);
    }

    public function test_las_cifras_cuentan_propietarios_y_lotes(): void
    {
        $this->actingAs(User::factory()->create());

        RegistroDePropietario::factory()->conLote(Calle::Clavel, '12', SituacionDelLote::CasaTerminada)->conLote(Calle::Clavel, '14')->create();
        RegistroDePropietario::factory()->conLote(Calle::Malva, '21', SituacionDelLote::EnConstruccion)->create();

        RegistroDePropietario::factory()->verificado()->create();

        Livewire::test(ResumenDeRegistros::class)
            ->assertSee('Propietarios registrados')
            ->assertSee('1 con todo verificado')
            ->assertSee('Lotes registrados')
            ->assertSee('1 más en construcción');

        $this->assertSame(3, RegistroDePropietario::query()->count());
    }

    public function test_se_puede_ver_el_detalle_y_borrar_un_registro(): void
    {
        $this->actingAs(User::factory()->create());

        $registro = RegistroDePropietario::factory()->conContactoAdicional('Luis Ejemplo')->create(['nombre' => 'Marta Ejemplo']);

        // El contenido del panel lateral lo pinta el navegador; aquí solo se
        // comprueba que la acción existe y se puede abrir sobre el registro.
        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertTableActionExists('view')
            ->mountTableAction('view', $registro)
            ->assertHasNoErrors();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->callTableAction('delete', $registro);

        $this->assertModelMissing($registro);
        $this->assertDatabaseCount('lotes_registrados', 0);
        $this->assertDatabaseCount('contactos_del_registro', 0);
    }

    public function test_la_lista_distingue_cada_verificacion_y_se_filtra_por_ellas(): void
    {
        $this->actingAs(User::factory()->create());

        $completo = RegistroDePropietario::factory()->verificado()->create();
        $soloCorreoDadoYVerificado = RegistroDePropietario::factory()->sinTelefono()->conCorreoVerificado()->create();
        $faltaElCelular = RegistroDePropietario::factory()->conCorreoVerificado()->create();
        $faltaTodo = RegistroDePropietario::factory()->create();

        $lista = Livewire::test(ListRegistrosDePropietarios::class)
            ->assertCanSeeTableRecords([$completo, $soloCorreoDadoYVerificado, $faltaElCelular, $faltaTodo]);

        // Quien dio un solo medio y lo verificó está completo; quien dio los dos
        // y verificó uno, no.
        $lista->filterTable('verificacion', 'completa')
            ->assertCanSeeTableRecords([$completo, $soloCorreoDadoYVerificado])
            ->assertCanNotSeeTableRecords([$faltaElCelular, $faltaTodo]);

        $lista->filterTable('verificacion', 'falta')
            ->assertCanSeeTableRecords([$faltaElCelular, $faltaTodo])
            ->assertCanNotSeeTableRecords([$completo, $soloCorreoDadoYVerificado]);

        $lista->filterTable('verificacion', 'celular_pendiente')
            ->assertCanSeeTableRecords([$faltaElCelular, $faltaTodo])
            ->assertCanNotSeeTableRecords([$completo, $soloCorreoDadoYVerificado]);

        $lista->filterTable('verificacion', 'correo_pendiente')
            ->assertCanSeeTableRecords([$faltaTodo])
            ->assertCanNotSeeTableRecords([$completo, $soloCorreoDadoYVerificado, $faltaElCelular]);
    }

    public function test_quien_dio_un_solo_medio_se_valida_con_ese_medio_verificado(): void
    {
        $this->actingAs(User::factory()->create());

        $soloCorreo = RegistroDePropietario::factory()->sinTelefono()->conCorreoVerificado()->create();
        $dioLosDos = RegistroDePropietario::factory()->conCorreoVerificado()->create();

        $this->assertTrue($soloCorreo->estaVerificado());
        $this->assertFalse($dioLosDos->estaVerificado());

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertTableActionVisible('validar', $soloCorreo)
            ->assertTableActionHidden('validar', $dioLosDos);

        $this->assertSame(1, RegistroDePropietario::query()->porValidar()->count());
    }

    public function test_el_csv_dice_cuando_no_dieron_un_medio(): void
    {
        RegistroDePropietario::factory()->sinTelefono()->conCorreoVerificado()->create(['nombre' => 'Solo Correo']);

        $texto = stream_get_contents(ListaCsv::flujo(RegistroDePropietario::query()->with(['lotes', 'contactos'])->get()));

        $this->assertMatchesRegularExpression('/Solo Correo.*,"No lo dio",Pendiente\s*$/m', $texto);
    }

    public function test_el_csv_marca_cada_verificacion_y_la_validacion(): void
    {
        $this->actingAs(User::factory()->create());

        RegistroDePropietario::factory()->validado()->create(['nombre' => 'Todo Hecho']);
        RegistroDePropietario::factory()->conCorreoVerificado()->create(['nombre' => 'Solo Correo']);

        $texto = stream_get_contents(ListaCsv::flujo(RegistroDePropietario::query()->with(['lotes', 'contactos'])->oldest()->get()));

        // Las fechas llevan espacio, así que fputcsv las entrecomilla.
        $fecha = '"\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}"';

        $this->assertStringContainsString('"Correo verificado","Celular verificado","Validado por la Administración"', $texto);
        $this->assertMatchesRegularExpression('/Todo Hecho.*'.$fecha.','.$fecha.','.$fecha.'\s*$/m', $texto);
        $this->assertMatchesRegularExpression('/Solo Correo.*'.$fecha.',Pendiente,Pendiente\s*$/m', $texto);
    }

    public function test_un_registro_verificado_se_valida_con_nombre_fecha_y_nota(): void
    {
        $quien = User::factory()->create(['name' => 'Lourdes Revisora']);
        $this->actingAs($quien);

        $registro = RegistroDePropietario::factory()->verificado()->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->callTableAction('validar', $registro, data: ['nota' => 'Escritura mostrada'])
            ->assertHasNoTableActionErrors();

        $registro->refresh();

        $this->assertTrue($registro->estaValidado());
        $this->assertSame($quien->id, $registro->validado_por);
        $this->assertSame('Lourdes Revisora', $registro->validador->name);
        $this->assertSame('Escritura mostrada', $registro->validacion_nota);
    }

    public function test_la_accion_de_validar_solo_se_ofrece_con_los_dos_medios_verificados(): void
    {
        $this->actingAs(User::factory()->create());

        $incompleto = RegistroDePropietario::factory()->conCorreoVerificado()->create();
        $completo = RegistroDePropietario::factory()->verificado()->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertTableActionHidden('validar', $incompleto)
            ->assertTableActionVisible('validar', $completo);
    }

    public function test_el_modelo_se_niega_a_validar_un_registro_sin_verificar(): void
    {
        $registro = RegistroDePropietario::factory()->conTelefonoVerificado()->create();

        $this->expectException(LogicException::class);

        $registro->validar(User::factory()->create());
    }

    public function test_quitar_la_validacion_deja_el_registro_por_validar(): void
    {
        $this->actingAs(User::factory()->create());

        $registro = RegistroDePropietario::factory()->validado(nota: 'ok')->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->assertTableActionHidden('validar', $registro)
            ->callTableAction('quitarValidacion', $registro);

        $registro->refresh();

        $this->assertFalse($registro->estaValidado());
        $this->assertNull($registro->validado_por);
        $this->assertNull($registro->validacion_nota);
        $this->assertTrue($registro->estaVerificado());
    }

    public function test_se_filtra_por_validados_y_por_validar_y_el_menu_cuenta_lo_pendiente(): void
    {
        $this->actingAs(User::factory()->create());

        $validado = RegistroDePropietario::factory()->validado()->create();
        $porValidar = RegistroDePropietario::factory()->verificado()->create();
        RegistroDePropietario::factory()->conCorreoVerificado()->create();

        Livewire::test(ListRegistrosDePropietarios::class)
            ->filterTable('validado_en', true)
            ->assertCanSeeTableRecords([$validado])
            ->assertCanNotSeeTableRecords([$porValidar]);

        // Solo cuentan los que ya tienen los dos medios verificados y falta validar.
        $this->assertSame('1', RegistrosDePropietariosResource::getNavigationBadge());
        $this->assertSame(1, RegistroDePropietario::query()->porValidar()->count());
    }

    public function test_no_se_puede_dar_de_alta_desde_el_panel(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/registros/nuevo')->assertNotFound();
        $this->get('/admin/registros/create')->assertNotFound();
    }

    public function test_el_csv_trae_un_renglon_por_lote_con_acentos_y_sin_formulas(): void
    {
        $this->actingAs(User::factory()->create());

        RegistroDePropietario::factory()
            ->conLote(Calle::Margarita, '128', SituacionDelLote::CasaTerminada)
            ->conLote(Calle::Nube, '45')
            ->conContactoAdicional('Luis Ejemplo')
            ->create(['nombre' => '=HYPERLINK("http://malo.example","Pérez")', 'telefono' => '5511112222', 'correo' => 'a@b.test']);

        $registros = RegistroDePropietario::query()->with(['lotes', 'contactos'])->get();
        $texto = stream_get_contents(ListaCsv::flujo($registros));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $texto);
        $this->assertStringContainsString('Núm. oficial', $texto);
        $this->assertStringContainsString("'=HYPERLINK", $texto);
        $this->assertStringNotContainsString(',=HYPERLINK', $texto);
        $this->assertStringContainsString('Casa terminada', $texto);
        // Un renglón por lote: el encabezado y los dos lotes pedidos.
        $this->assertCount(3, array_filter(explode("\n", trim($texto))));

        Livewire::test(ListRegistrosDePropietarios::class)
            ->callAction('descargarCsv')
            ->assertFileDownloaded();
    }

    public function test_celda_neutraliza_solo_lo_que_empieza_como_formula(): void
    {
        $this->assertSame("'=1+1", ListaCsv::celda('=1+1'));
        $this->assertSame("'+52", ListaCsv::celda('+52'));
        $this->assertSame("'-3", ListaCsv::celda('-3'));
        $this->assertSame("'@cuenta", ListaCsv::celda('@cuenta'));
        $this->assertSame('Pérez', ListaCsv::celda('Pérez'));
        $this->assertSame('', ListaCsv::celda(null));
        $this->assertSame('4', ListaCsv::celda(4));
    }
}
