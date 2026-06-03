<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFraccionamientoRequest;
use App\Http\Requests\UpdateFraccionamientoRequest;
use App\Models\Fraccionamiento;
use Illuminate\Http\Request;

class FraccionamientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fraccionamientos = Fraccionamiento::latest()->paginate(10);
        return view('fraccionamientos.index', compact('fraccionamientos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fraccionamientos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFraccionamientoRequest $request)
    {
        Fraccionamiento::create($request->validated());
        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Fraccionamiento $fraccionamiento)
    {
        return view('fraccionamientos.show', compact('fraccionamiento'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fraccionamiento $fraccionamiento)
    {
        return view('fraccionamientos.edit', compact('fraccionamiento'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFraccionamientoRequest $request, Fraccionamiento $fraccionamiento)
    {
        $fraccionamiento->update($request->validated());
        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fraccionamiento $fraccionamiento)
    {
        $fraccionamiento->delete();
        return redirect()->route('fraccionamientos.index')
            ->with('success', 'Fraccionamiento eliminado exitosamente.');
    }
}
