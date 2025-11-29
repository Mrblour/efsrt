<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\PlanEstudio;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index(Request $request)
    {
        // Traemos módulos con su plan y el programa del plan
        $query = Modulo::with(['planEstudio.programaEstudio']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('numero', 'like', "%{$search}%")
                  ->orWhereHas('planEstudio', function($q) use ($search) {
                      $q->where('anio', 'like', "%{$search}%")
                        ->orWhereHas('programaEstudio', function($q2) use ($search) {
                            $q2->where('nombre', 'like', "%{$search}%");
                        });
                  });
        }

        $modulos = $query->paginate(10);
        return view('modulo.index', compact('modulos'));
    }

    public function create()
    {
        $planes = PlanEstudio::all();
        return view('modulo.create', compact('planes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'horas'    => 'required|integer|min:0',
            'creditos' => 'required|integer|min:0',
            'numero'   => 'required|integer|min:1',
            'id_plan'  => 'required|exists:planes_estudio,id',
        ]);

        Modulo::create([
            'nombre'   => $request->nombre,
            'horas'    => $request->horas,
            'creditos' => $request->creditos,
            'numero'   => $request->numero,
            'id_plan'  => $request->id_plan,
        ]);

        return redirect()
            ->route('modulo.index')
            ->with('success', 'Módulo registrado correctamente.');
    }

    public function edit(Modulo $modulo)
    {
        $planes = PlanEstudio::all();
        return view('modulo.edit', compact('modulo', 'planes'));
    }

    public function update(Request $request, Modulo $modulo)
    {
        $request->validate([
            'nombre'   => 'required|string|max:255',
            'horas'    => 'required|integer|min:0',
            'creditos' => 'required|integer|min:0',
            'numero'   => 'required|integer|min:1',
            'id_plan'  => 'required|exists:planes_estudio,id',
        ]);

        $modulo->update([
            'nombre'   => $request->nombre,
            'horas'    => $request->horas,
            'creditos' => $request->creditos,
            'numero'   => $request->numero,
            'id_plan'  => $request->id_plan,
        ]);

        return redirect()
            ->route('modulo.index')
            ->with('success', 'Módulo actualizado correctamente.');
    }

    public function destroy(Modulo $modulo)
    {
        $modulo->delete();

        return redirect()
            ->route('modulo.index')
            ->with('success', 'Módulo eliminado correctamente.');
    }
}
