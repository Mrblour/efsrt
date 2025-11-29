<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Semestre;

class SemestreController extends Controller
{
    public function index()
    {
        $semestres = Semestre::all();
        return view('semestres.index', compact('semestres'));
    }

    public function create()
    {
        return view('semestres.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        Semestre::create($request->only('nombre'));

        return redirect()->route('semestres.index')->with('success', 'Semestre creado');
    }

    public function edit(Semestre $semestre)
    {
        return view('semestres.edit', compact('semestre'));
    }

    public function update(Request $request, Semestre $semestre)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
        ]);

        $semestre->update($request->only('nombre'));

        return redirect()->route('semestres.index')->with('success', 'Semestre actualizado');
    }

    public function destroy(Semestre $semestre)
    {
        $semestre->delete();
        return redirect()->route('semestres.index')->with('success', 'Semestre eliminado');
    }
}
