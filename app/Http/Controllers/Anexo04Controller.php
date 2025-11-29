<?php

namespace App\Http\Controllers;

use App\Models\Anexo04;
use App\Models\Empresa;
use App\Models\EFSRT;
use Illuminate\Http\Request;

class Anexo04Controller extends Controller
{
    public function index()
    {
        $anexos = Anexo04::with(['empresa', 'efsrt'])->get();
        return view('anexo04.index', compact('anexos'));
    }

    public function create()
    {
        $empresas = Empresa::all();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        return view('anexo04.create', compact('empresas', 'efsrtList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'problemas_detectados' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'id_empresa' => 'nullable|exists:empresas,id',
            'id_EFSRT' => 'nullable|exists:efsrt,id'
        ]);

        Anexo04::create($request->all());

        return redirect()->route('anexo04.index')->with('success', 'Anexo 04 creado correctamente.');
    }

    public function edit(Anexo04 $anexo04)
    {
        $empresas = Empresa::all();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        return view('anexo04.edit', compact('anexo04', 'empresas', 'efsrtList'));
    }

    public function update(Request $request, Anexo04 $anexo04)
    {
        $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'problemas_detectados' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'id_empresa' => 'nullable|exists:empresas,id',
            'id_EFSRT' => 'nullable|exists:efsrt,id'
        ]);

        $anexo04->update($request->all());

        return redirect()->route('anexo04.index')->with('success', 'Anexo 04 actualizado correctamente.');
    }

    public function destroy(Anexo04 $anexo04)
    {
        $anexo04->delete();
        return redirect()->route('anexo04.index')->with('success', 'Anexo 04 eliminado correctamente.');
    }
}
