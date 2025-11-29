<?php

namespace App\Http\Controllers;

use App\Models\IndicadorEvaluacion;
use App\Models\TipoIndicador;
use Illuminate\Http\Request;

class IndicadorEvaluacionController extends Controller
{
    public function index()
    {
        $indicadores = IndicadorEvaluacion::with('tipoIndicador')->get();
        return view('indicadorevaluacion.index', compact('indicadores'));
    }

    public function create()
    {
        $tipos = TipoIndicador::all();
        return view('indicadorevaluacion.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item' => 'required|integer|min:1|max:10',
            'nombre' => 'required|string|max:255',
            'id_tipo_indicador' => 'required|exists:tipos_indicadores,id',
            'estado' => 'required|in:0,1',
        ]);

        IndicadorEvaluacion::create($request->all());

        return redirect()->route('indicadorevaluacion.index')
                         ->with('success', 'Indicador de evaluación creado correctamente.');
    }

    public function edit(IndicadorEvaluacion $indicadorevaluacion)
    {
        $tipos = TipoIndicador::all();
        return view('indicadorevaluacion.edit', compact('indicadorevaluacion', 'tipos'));
    }

    public function update(Request $request, IndicadorEvaluacion $indicadorevaluacion)
    {
        $request->validate([
            'item' => 'required|integer|min:1|max:10',
            'nombre' => 'required|string|max:255',
            'id_tipo_indicador' => 'required|exists:tipos_indicadores,id',
            'estado' => 'required|in:0,1',
        ]);

        $indicadorevaluacion->update($request->all());

        return redirect()->route('indicadorevaluacion.index')
                         ->with('success', 'Indicador de evaluación actualizado correctamente.');
    }

    public function destroy(IndicadorEvaluacion $indicadorevaluacion)
    {
        $indicadorevaluacion->delete();
        return redirect()->route('indicadorevaluacion.index')
                         ->with('success', 'Indicador de evaluación eliminado correctamente.');
    }
}
