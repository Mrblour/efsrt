<?php

namespace App\Http\Controllers;

use App\Models\TipoIndicador;
use Illuminate\Http\Request;

class TipoIndicadorController extends Controller
{
    public function index()
    {
        $tipoindicadores = TipoIndicador::all();
        return view('tipoindicadores.index', compact('tipoindicadores'));
    }

    public function create()
    {
        // Obtener números ya usados
        $numerosUsados = TipoIndicador::pluck('item')->toArray();
        return view('tipoindicadores.create', compact('numerosUsados'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
        ]);

        TipoIndicador::create($request->all());

        return redirect()->route('tipoindicadores.index')->with('success', 'Tipo de indicador creado correctamente.');
    }

    public function edit(TipoIndicador $tipoindicadore)
    {
        // Obtener números ya usados (excepto el actual)
        $numerosUsados = TipoIndicador::where('id', '!=', $tipoindicadore->id)->pluck('item')->toArray();
        return view('tipoindicadores.edit', compact('tipoindicadore', 'numerosUsados'));
    }

    public function update(Request $request, TipoIndicador $tipoindicadore)
    {
        $request->validate([
            'item' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
        ]);

        $tipoindicadore->update($request->all());

        return redirect()->route('tipoindicadores.index')->with('success', 'Tipo de indicador actualizado correctamente.');
    }

    public function destroy(TipoIndicador $tipoindicadore)
    {
        $tipoindicadore->delete();

        return redirect()->route('tipoindicadores.index')->with('success', 'Tipo de indicador eliminado correctamente.');
    }
}
