<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyFeeRequest;
use App\Models\Fraccionamiento;
use App\Models\MonthlyFee;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;

class MonthlyFeeController extends Controller
{
    public function index(Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('view', $fraccionamiento);

        $currentFee = $fraccionamiento->currentFee();
        $scheduledFee = $fraccionamiento->scheduledFee();

        $history = $fraccionamiento->monthlyFees()
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('monthly-fees.index', compact('fraccionamiento', 'currentFee', 'scheduledFee', 'history'));
    }

    public function create(Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('update', $fraccionamiento);

        return view('monthly-fees.create', compact('fraccionamiento'));
    }

    public function store(StoreMonthlyFeeRequest $request, Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('update', $fraccionamiento);

        $data = $request->validated();

        // Si no hay tipo de recargo, asegurarse de que tampoco haya valor
        if (empty($data['surcharge_type'])) {
            $data['surcharge_type'] = null;
            $data['surcharge_value'] = null;
        }

        // Reemplazar cualquier cuota programada a futuro (solo puede haber una)
        $fraccionamiento->monthlyFees()
            ->where('start_date', '>', Carbon::today())
            ->delete();

        $fraccionamiento->monthlyFees()->create($data);

        return redirect()->route('fraccionamientos.fees.index', $fraccionamiento)
            ->with('success', 'Cuota registrada exitosamente.');
    }

    public function destroy(Fraccionamiento $fraccionamiento, MonthlyFee $monthly_fee)
    {
        Gate::authorize('update', $fraccionamiento);

        if (!$monthly_fee->isFuture()) {
            return back()->with('error', 'Solo se pueden cancelar cuotas programadas a futuro. Las cuotas históricas se conservan para auditoría.');
        }

        $monthly_fee->delete();

        return redirect()->route('fraccionamientos.fees.index', $fraccionamiento)
            ->with('success', 'Cuota programada cancelada.');
    }
}
