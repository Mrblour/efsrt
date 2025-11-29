<?php

namespace App\Http\Controllers;

use App\Models\ProgramaEstudio;
use App\Models\Turno;
use Illuminate\Http\Request;

class ProgramaEstudioController extends Controller
{
    public function index(Request $request)
    {
        $query = ProgramaEstudio::with('turno');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhereHas('turno', function($q) use ($search) {
                      $q->where('turno', 'like', "%{$search}%");
                  });
        }

        $programas = $query->get();
        return view('programasestudios.index', compact('programas'));
    }

    public function create()
    {
        $turnos = Turno::all();
        return view('programasestudios.create', compact('turnos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'idTurno' => 'required|exists:turnos,id',
            'codigo' => 'required|string|max:50',
        ]);

        ProgramaEstudio::create([
            'nombre' => $request->nombre,
            'logo' => $request->logo,
            'idTurno' => $request->idTurno, // Campo de la tabla `programas_estudios`
            'codigo' => $request->codigo,
        ]);

        return redirect()->route('programasestudios.index')->with('success', 'Programa de estudio creado correctamente.');
    }

    public function edit(ProgramaEstudio $programasestudio)
    {
        $turnos = Turno::all();
        return view('programasestudios.edit', compact('programasestudio', 'turnos'));
    }

    public function update(Request $request, ProgramaEstudio $programasestudio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'idTurno' => 'required|exists:turnos,id',
            'codigo' => 'required|string|max:50',
        ]);

        $programasestudio->update([
            'nombre' => $request->nombre,
            'logo' => $request->logo,
            'idTurno' => $request->idTurno, // Campo de la tabla `programas_estudios`
            'codigo' => $request->codigo,
        ]);

        return redirect()->route('programasestudios.index')->with('success', 'Programa de estudio actualizado correctamente.');
    }

    public function destroy(ProgramaEstudio $programasestudio)
    {
        $programasestudio->delete();
        return redirect()->route('programasestudios.index')->with('success', 'Programa de estudio eliminado correctamente.');
    }
}