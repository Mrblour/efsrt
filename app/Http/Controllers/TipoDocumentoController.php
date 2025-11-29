<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    public function index()
    {
        $tipos = TipoDocumento::all();
        return view('tipodocumento.index', compact('tipos'));
    }

    public function create()
    {
        return view('tipodocumento.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string|max:255',
        ]);

        TipoDocumento::create($request->all());
        return redirect()->route('tipodocumento.index')->with('success', 'Tipo de documento creado correctamente.');
    }

    public function edit(TipoDocumento $tipodocumento)
    {
        return view('tipodocumento.edit', compact('tipodocumento'));
    }

    public function update(Request $request, TipoDocumento $tipodocumento)
    {
        $request->validate([
            'tipo' => 'required|string|max:255',
        ]);

        $tipodocumento->update($request->all());
        return redirect()->route('tipodocumento.index')->with('success', 'Tipo de documento actualizado correctamente.');
    }

    public function destroy(TipoDocumento $tipodocumento)
    {
        $tipodocumento->delete();
        return redirect()->route('tipodocumento.index')->with('success', 'Tipo de documento eliminado correctamente.');
    }
}
