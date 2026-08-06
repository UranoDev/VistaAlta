<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFraccionamientoRequest;
use App\Http\Requests\UpdateFraccionamientoRequest;
use App\Models\Fraccionamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class FraccionamientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Fraccionamiento::with(['administrator', 'monthlyFees'])->latest();

        if (!$user->isSuperAdmin()) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $fraccionamientos = $query->paginate(10);
        return view('fraccionamientos.index', compact('fraccionamientos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', Fraccionamiento::class);
        return view('fraccionamientos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFraccionamientoRequest $request)
    {
        Gate::authorize('create', Fraccionamiento::class);
        $fraccionamiento = Fraccionamiento::create($request->validated());

        if ($request->has('users') && Auth::user()->isSuperAdmin()) {
            $fraccionamiento->users()->sync($request->users);
        }

        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('view', $fraccionamiento);
        return view('fraccionamientos.show', compact('fraccionamiento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('update', $fraccionamiento);
        $propietarios = $fraccionamiento->owners()->orderBy('name')->get();
        return view('fraccionamientos.edit', compact('fraccionamiento', 'propietarios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFraccionamientoRequest $request, Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('update', $fraccionamiento);
        $fraccionamiento->update($request->validated());

        if ($request->has('users') && Auth::user()->isSuperAdmin()) {
            $fraccionamiento->users()->sync($request->users);
        }

        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fraccionamiento $fraccionamiento)
    {
        Gate::authorize('delete', $fraccionamiento);
        $fraccionamiento->delete();
        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento eliminado exitosamente.');
    }
}
