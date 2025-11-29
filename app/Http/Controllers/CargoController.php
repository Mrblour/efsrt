<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use Illuminate\Http\Request;

class CargoController extends Controller
{
    public function index(Request $request)
    {
        $query = Cargo::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%");
        }

        $cargos = $query->get();
        return view('cargos.index', compact('cargos'));
    }

    public function create()
    {
        return view('cargos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        Cargo::create($request->all());

        return redirect()->route('cargos.index')->with('success', 'Cargo creado correctamente.');
    }

    public function edit($id)
    {
        $cargo = Cargo::findOrFail($id);
        return view('cargos.edit', compact('cargo'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $cargo = Cargo::findOrFail($id);
        $cargo->update($request->all());

        return redirect()->route('cargos.index')->with('success', 'Cargo actualizado correctamente.');
    }

    public function destroy($id)
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->delete();

        return redirect()->route('cargos.index')->with('success', 'Cargo eliminado correctamente.');
    }
}
