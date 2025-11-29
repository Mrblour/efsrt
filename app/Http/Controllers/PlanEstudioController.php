<?php

namespace App\Http\Controllers;

use App\Models\PlanEstudio;
use App\Models\ProgramaEstudio;
use Illuminate\Http\Request;

class PlanEstudioController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanEstudio::with('programaEstudio');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('anio', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhereHas('programaEstudio', function($q) use ($search) {
                      $q->where('nombre', 'like', "%{$search}%");
                  });
        }

        $planes = $query->get();
        return view('planestudios.index', compact('planes'));
    }

    public function create()
    {
        $programas = ProgramaEstudio::all();
        return view('planestudios.create', compact('programas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'activo' => 'required|boolean',
            'id_programa' => 'required|exists:programas_estudios,id',
            'descripcion' => 'nullable|string'
        ]);

        PlanEstudio::create($request->all());

        return redirect()->route('planestudios.index')
                         ->with('success', 'Plan de estudio creado correctamente.');
    }

    public function edit($id)
    {
        $plan = PlanEstudio::findOrFail($id);
        $programas = ProgramaEstudio::all();
        return view('planestudios.edit', compact('plan', 'programas'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'anio' => 'required|integer',
            'activo' => 'required|boolean',
            'id_programa' => 'required|exists:programas_estudios,id',
            'descripcion' => 'nullable|string'
        ]);

        $plan = PlanEstudio::findOrFail($id);
        $plan->update($request->all());

        return redirect()->route('planestudios.index')
                         ->with('success', 'Plan de estudio actualizado correctamente.');
    }

    public function destroy($id)
    {
        $plan = PlanEstudio::findOrFail($id);
        $plan->delete();

        return redirect()->route('planestudios.index')
                         ->with('success', 'Plan de estudio eliminado correctamente.');
    }
}
