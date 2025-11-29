<?php

namespace App\Http\Controllers;

use App\Models\Turno;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function index(Request $request)
    {
        $query = Turno::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('turno', 'like', "%{$search}%");
        }

        $turnos = $query->get();
        return view('turnos.index', compact('turnos'));
    }

    public function create()
    {
        return view('turnos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'turno' => 'required|string|max:100',
        ]);

        Turno::create([
            'turno' => $request->turno
        ]);

        return redirect()->route('turnos.index')->with('success', 'Turno creado correctamente.');
    }

    public function edit(Turno $turno)
    {
        return view('turnos.edit', compact('turno'));
    }

    public function update(Request $request, Turno $turno)
    {
        $request->validate([
            'turno' => 'required|string|max:100',
        ]);

        $turno->update([
            'turno' => $request->turno
        ]);

        return redirect()->route('turnos.index')->with('success', 'Turno actualizado correctamente.');
    }

    public function destroy(Turno $turno)
    {
        $turno->delete();
        return redirect()->route('turnos.index')->with('success', 'Turno eliminado correctamente.');
    }
}
