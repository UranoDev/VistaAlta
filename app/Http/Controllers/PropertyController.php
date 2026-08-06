<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Fraccionamiento;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;

class PropertyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = Property::with(['fraccionamiento', 'owner'])->latest();

        if (!$user->isSuperAdmin()) {
            $assignedIds = $user->fraccionamientos()->pluck('fraccionamientos.id');
            $query->whereIn('fraccionamiento_id', $assignedIds);
        }

        $properties = $query->paginate(10);
        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        $fraccionamientos = $this->getUserFraccionamientos();
        $owners = Owner::orderBy('name')->get(['id', 'name', 'fraccionamiento_id']);
        return view('properties.create', compact('fraccionamientos', 'owners'));
    }

    public function store(StorePropertyRequest $request)
    {
        Property::create($request->validated());

        return redirect()->route('properties.index')
            ->with('success', 'Propiedad creada exitosamente.');
    }

    public function edit(Property $property)
    {
        $fraccionamientos = $this->getUserFraccionamientos();
        $owners = Owner::orderBy('name')->get(['id', 'name', 'fraccionamiento_id']);
        return view('properties.edit', compact('property', 'fraccionamientos', 'owners'));
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $property->update($request->validated());

        return redirect()->route('properties.index')
            ->with('success', 'Propiedad actualizada exitosamente.');
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('properties.index')
            ->with('success', 'Propiedad eliminada exitosamente.');
    }

    private function getUserFraccionamientos()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            return Fraccionamiento::orderBy('name')->get();
        }
        return $user->fraccionamientos()->orderBy('name')->get();
    }
}
