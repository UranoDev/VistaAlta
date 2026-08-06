<?php

namespace Tests\Feature;

use App\Models\Fraccionamiento;
use App\Models\MonthlyFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyFeeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private Fraccionamiento $fraccionamiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->fraccionamiento = Fraccionamiento::factory()->create();
    }

    // ── Acceso ──────────────────────────────────────────────────────────────

    public function test_superadmin_can_view_fees_index(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('fraccionamientos.fees.index', $this->fraccionamiento));

        $response->assertStatus(200);
        $response->assertViewHas('fraccionamiento');
    }

    public function test_admin_can_view_fees_of_assigned_fraccionamiento(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->fraccionamientos()->attach($this->fraccionamiento);

        $response = $this->actingAs($admin)
            ->get(route('fraccionamientos.fees.index', $this->fraccionamiento));

        $response->assertStatus(200);
    }

    public function test_admin_cannot_view_fees_of_unassigned_fraccionamiento(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // No se asigna al fraccionamiento

        $response = $this->actingAs($admin)
            ->get(route('fraccionamientos.fees.index', $this->fraccionamiento));

        $response->assertStatus(403);
    }

    public function test_can_view_create_form(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get(route('fraccionamientos.fees.create', $this->fraccionamiento));

        $response->assertStatus(200);
    }

    // ── Creación ────────────────────────────────────────────────────────────

    public function test_can_store_basic_fee(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800.00',
                'start_date' => today()->toDateString(),
                'surcharge_type' => '',
                'surcharge_value' => '',
            ]);

        $response->assertRedirect(route('fraccionamientos.fees.index', $this->fraccionamiento));
        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '800.00',
            'surcharge_type' => null,
            'surcharge_value' => null,
        ]);
    }

    public function test_can_store_fee_with_percentage_surcharge(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800.00',
                'start_date' => today()->toDateString(),
                'surcharge_type' => 'percentage',
                'surcharge_value' => '10',
            ]);

        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '800.00',
            'surcharge_type' => 'percentage',
            'surcharge_value' => '10.00',
        ]);
    }

    public function test_can_store_fee_with_fixed_surcharge(): void
    {
        $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800.00',
                'start_date' => today()->toDateString(),
                'surcharge_type' => 'fixed',
                'surcharge_value' => '80',
            ]);

        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'surcharge_type' => 'fixed',
            'surcharge_value' => '80.00',
        ]);
    }

    public function test_can_store_fee_with_future_start_date(): void
    {
        $futureDate = today()->addMonth()->toDateString();

        $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '1000.00',
                'start_date' => $futureDate,
                'surcharge_type' => '',
            ]);

        // Verificamos amount vía assertDatabaseHas y la fecha vía modelo
        // (SQLite puede almacenar date como datetime string)
        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '1000.00',
        ]);
        $fee = MonthlyFee::where('fraccionamiento_id', $this->fraccionamiento->id)->latest('id')->first();
        $this->assertEquals($futureDate, $fee->start_date->toDateString());
        $this->assertTrue($fee->isFuture());
    }

    public function test_creating_new_future_fee_replaces_existing_scheduled_fee(): void
    {
        // Crear una cuota programada a futuro existente
        $existing = MonthlyFee::factory()->future()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '900.00',
        ]);

        // Crear otra cuota futura → debe reemplazar la anterior
        $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '1000.00',
                'start_date' => today()->addMonths(2)->toDateString(),
                'surcharge_type' => '',
            ]);

        $this->assertDatabaseMissing('monthly_fees', ['id' => $existing->id]);
        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '1000.00',
        ]);
    }

    public function test_creating_new_active_fee_preserves_historical_fees(): void
    {
        // Cuota histórica (no debe eliminarse)
        $historical = MonthlyFee::factory()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '500.00',
            'start_date' => today()->subYear(),
        ]);

        $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800.00',
                'start_date' => today()->toDateString(),
                'surcharge_type' => '',
            ]);

        // La cuota histórica sigue en la BD
        $this->assertDatabaseHas('monthly_fees', ['id' => $historical->id]);
        // Y también existe la nueva
        $this->assertDatabaseHas('monthly_fees', [
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => '800.00',
        ]);
    }

    // ── Validaciones ────────────────────────────────────────────────────────

    public function test_amount_is_required(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '',
                'start_date' => today()->toDateString(),
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_start_date_is_required(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800',
                'start_date' => '',
            ]);

        $response->assertSessionHasErrors('start_date');
    }

    public function test_surcharge_value_required_when_type_is_set(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post(route('fraccionamientos.fees.store', $this->fraccionamiento), [
                'amount' => '800',
                'start_date' => today()->toDateString(),
                'surcharge_type' => 'percentage',
                'surcharge_value' => '',
            ]);

        $response->assertSessionHasErrors('surcharge_value');
    }

    // ── Eliminación ─────────────────────────────────────────────────────────

    public function test_can_delete_scheduled_future_fee(): void
    {
        $fee = MonthlyFee::factory()->future()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('fraccionamientos.fees.destroy', [$this->fraccionamiento, $fee]));

        $response->assertRedirect(route('fraccionamientos.fees.index', $this->fraccionamiento));
        $this->assertDatabaseMissing('monthly_fees', ['id' => $fee->id]);
    }

    public function test_cannot_delete_active_or_historical_fee(): void
    {
        $fee = MonthlyFee::factory()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'start_date' => today(),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete(route('fraccionamientos.fees.destroy', [$this->fraccionamiento, $fee]));

        // Debe regresar sin eliminar (error en sesión)
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('monthly_fees', ['id' => $fee->id]);
    }

    // ── Lógica de cuota vigente ──────────────────────────────────────────────

    public function test_current_fee_returns_most_recent_active(): void
    {
        Carbon::setTestNow('2026-06-01');

        $old = MonthlyFee::factory()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => 500,
            'start_date' => '2026-01-01',
        ]);
        $current = MonthlyFee::factory()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => 800,
            'start_date' => '2026-05-01',
        ]);
        MonthlyFee::factory()->create([
            'fraccionamiento_id' => $this->fraccionamiento->id,
            'amount' => 1000,
            'start_date' => '2026-07-01',
        ]);

        $this->assertEquals($current->id, $this->fraccionamiento->currentFee()->id);

        Carbon::setTestNow();
    }

    public function test_amount_with_percentage_surcharge(): void
    {
        $fee = MonthlyFee::factory()->withPercentageSurcharge(10)->make(['amount' => 800]);

        $this->assertEquals(880.0, $fee->amountWithSurcharge());
    }

    public function test_amount_with_fixed_surcharge(): void
    {
        $fee = MonthlyFee::factory()->withFixedSurcharge(80)->make(['amount' => 800]);

        $this->assertEquals(880.0, $fee->amountWithSurcharge());
    }

    public function test_surcharge_is_not_cumulative(): void
    {
        // El recargo es fijo independientemente de cuántos meses tarde
        $fee = MonthlyFee::factory()->withFixedSurcharge(80)->make(['amount' => 800]);

        // Mismo resultado sin importar cuántas veces se calcule
        $this->assertEquals(880.0, $fee->amountWithSurcharge());
        $this->assertEquals(880.0, $fee->amountWithSurcharge());
    }
}
