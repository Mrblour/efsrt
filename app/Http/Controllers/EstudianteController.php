<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with(['persona', 'programaEstudio']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%");
            })->orWhereHas('programaEstudio', function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        $estudiantes = $query->get();
        return view('estudiantes.index', compact('estudiantes'));
    }

    public function create()
    {
        return view('estudiantes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|unique:estudiantes,id',
            'id_programa' => 'required|integer',
        ]);

        Estudiante::create([
            'id' => $request->id,
            'id_programa' => $request->id_programa,
        ]);

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante creado correctamente');
    }

    public function edit($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        return view('estudiantes.edit', compact('estudiante'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_programa' => 'required|integer',
        ]);

        $estudiante = Estudiante::findOrFail($id);
        $estudiante->update([
            'id_programa' => $request->id_programa,
        ]);

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado correctamente');
    }

    public function destroy($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado correctamente');
    }
}
