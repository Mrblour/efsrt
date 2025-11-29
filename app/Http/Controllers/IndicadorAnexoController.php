<?php

namespace App\Http\Controllers;

use App\Models\IndicadorAnexo;
use App\Models\Anexo05;
use App\Models\IndicadorEvaluacion;
use Illuminate\Http\Request;

class IndicadorAnexoController extends Controller
{
    public function index()
    {
        // Traemos con relaciones para mostrar datos de anexo e indicador
        $indicadores = IndicadorAnexo::with(['anexo05', 'indicadorEvaluacion'])->get();

        return view('indicadoresanexos.index', compact('indicadores'));
    }

    public function create()
    {
        $anexos = Anexo05::all();
        $indicadores = IndicadorEvaluacion::all();

        return view('indicadoresanexos.create', compact('anexos', 'indicadores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_anexo' => 'required|exists:anexos_05,id',
            'id_indicador' => 'required|exists:indicadores_evaluacion,id',
            'calificacion' => 'required|numeric'
        ]);

        IndicadorAnexo::create($request->only(['id_anexo', 'id_indicador', 'calificacion']));

        return redirect()->route('indicadoresanexos.index')
            ->with('success', 'Indicador Anexo creado correctamente.');
    }

    public function edit(IndicadorAnexo $indicadoresanexo)
    {
        $anexos = Anexo05::all();
        $indicadores = IndicadorEvaluacion::all();

        return view('indicadoresanexos.edit', compact('indicadoresanexo', 'anexos', 'indicadores'));
    }

    public function update(Request $request, IndicadorAnexo $indicadoresanexo)
    {
        $request->validate([
            'id_anexo' => 'required|exists:anexos_05,id',
            'id_indicador' => 'required|exists:indicadores_evaluacion,id',
            'calificacion' => 'required|numeric'
        ]);

        $indicadoresanexo->update($request->only(['id_anexo', 'id_indicador', 'calificacion']));

        return redirect()->route('indicadoresanexos.index')
            ->with('success', 'Indicador Anexo actualizado correctamente.');
    }

    public function destroy(IndicadorAnexo $indicadoresanexo)
    {
        $indicadoresanexo->delete();

        return redirect()->route('indicadoresanexos.index')
            ->with('success', 'Indicador Anexo eliminado correctamente.');
    }
}
