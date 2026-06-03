<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOwnerRequest;
use App\Http\Requests\UpdateOwnerRequest;
use App\Models\Fraccionamiento;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $owners = Owner::with('fraccionamiento')->latest()->paginate(10);
        return view('owners.index', compact('owners'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fraccionamientos = Fraccionamiento::all();
        return view('owners.create', compact('fraccionamientos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOwnerRequest $request)
    {
        Owner::create($request->validated());

        return redirect()->route('owners.index')
            ->with('success', 'Propietario creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Owner $owner)
    {
        return view('owners.show', compact('owner'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Owner $owner)
    {
        $fraccionamientos = Fraccionamiento::all();
        return view('owners.edit', compact('owner', 'fraccionamientos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOwnerRequest $request, Owner $owner)
    {
        $owner->update($request->validated());

        return redirect()->route('owners.index')
            ->with('success', 'Propietario actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Owner $owner)
    {
        $owner->delete();

        return redirect()->route('owners.index')
            ->with('success', 'Propietario eliminado exitosamente.');
    }
}
