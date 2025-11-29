<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Persona;
use App\Models\ProgramaEstudio;
use Illuminate\Http\Request;

class DocenteController extends Controller
{
    public function index(Request $request)
    {
        // Carga las relaciones con persona y programa
        $query = Docente::with(['persona', 'programaEstudio']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%");
            })->orWhere('especialidad', 'like', "%{$search}%")
              ->orWhereHas('programaEstudio', function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        $docentes = $query->get();
        return view('docentes.index', compact('docentes'));
    }

    public function create()
    {
        // Solo personas que aún no son docentes
        $personas = Persona::doesntHave('docente')->get();
        $programas = ProgramaEstudio::all();
        return view('docentes.create', compact('personas', 'programas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_persona' => 'required|exists:personas,id|unique:docentes,id',
            'id_programa' => 'nullable|exists:programas_estudios,id',
            'especialidad' => 'nullable|string|max:80',
        ]);

        Docente::create([
            'id' => $request->id_persona,
            'id_programa' => $request->id_programa,
            'especialidad' => $request->especialidad,
        ]);

        return redirect()->route('docentes.index')->with('success', 'Docente creado correctamente.');
    }

    public function edit(Docente $docente)
    {
        $personas = Persona::all();
        $programas = ProgramaEstudio::all();
        return view('docentes.edit', compact('docente', 'personas', 'programas'));
    }

    public function update(Request $request, Docente $docente)
    {
        $request->validate([
            'id_programa' => 'nullable|exists:programas_estudios,id',
            'especialidad' => 'nullable|string|max:80',
        ]);

        $docente->update([
            'id_programa' => $request->id_programa,
            'especialidad' => $request->especialidad,
        ]);

        return redirect()->route('docentes.index')->with('success', 'Docente actualizado correctamente.');
    }

    public function destroy(Docente $docente)
    {
        // Solo eliminar el docente, no la persona
        $docente->delete();

        return redirect()->route('docentes.index')->with('success', 'Docente eliminado correctamente.');
    }
}
