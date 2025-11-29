<?php

namespace App\Http\Controllers;

use App\Models\VisitaAnexo04;
use App\Models\Anexo04;
use Illuminate\Http\Request;

class VisitaAnexo04Controller extends Controller
{
    public function index()
    {
        $visitas = VisitaAnexo04::with('anexo04')->get();
        return view('visitaanexo04.index', compact('visitas'));
    }

    public function create()
    {
        $anexos = Anexo04::all();
        return view('visitaanexo04.create', compact('anexos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|integer',
            'fecha' => 'required|date',
            'tareas' => 'required|string',
            'porcentaje_avance' => 'required|numeric|min:0|max:100',
            'idAnexo' => 'required|exists:anexos_04,id',
            'foto1' => 'nullable|string|max:255',
            'foto2' => 'nullable|string|max:255',
            'foto3' => 'nullable|string|max:255',
        ]);

        VisitaAnexo04::create($request->all());

        return redirect()->route('visitaanexo04.index')
                         ->with('success', 'Visita registrada correctamente.');
    }

    public function edit(VisitaAnexo04 $visitaanexo04)
    {
        $anexos = Anexo04::all();
        return view('visitaanexo04.edit', compact('visitaanexo04', 'anexos'));
    }

    public function update(Request $request, VisitaAnexo04 $visitaanexo04)
    {
        $request->validate([
            'numero' => 'required|integer',
            'fecha' => 'required|date',
            'tareas' => 'required|string',
            'porcentaje_avance' => 'required|numeric|min:0|max:100',
            'idAnexo' => 'required|exists:anexos_04,id',
            'foto1' => 'nullable|string|max:255',
            'foto2' => 'nullable|string|max:255',
            'foto3' => 'nullable|string|max:255',
        ]);

        $visitaanexo04->update($request->all());

        return redirect()->route('visitaanexo04.index')
                         ->with('success', 'Visita actualizada correctamente.');
    }

    public function destroy(VisitaAnexo04 $visitaanexo04)
    {
        $visitaanexo04->delete();
        return redirect()->route('visitaanexo04.index')
                         ->with('success', 'Visita eliminada correctamente.');
    }
}
