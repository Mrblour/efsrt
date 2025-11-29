<?php

namespace App\Http\Controllers;

use App\Models\Anexo03;
use App\Models\Empresa;
use App\Models\EFSRT;
use App\Models\ProgramaEstudio;
use App\Models\Modulo;
use Illuminate\Http\Request;

class Anexo03Controller extends Controller
{
    public function index()
    {
        $anexos = Anexo03::with(['empresa', 'efsrt'])->get();
        return view('anexo03.index', compact('anexos'));
    }

    public function create()
    {
        $empresas = Empresa::all();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        $programasEstudio = ProgramaEstudio::all();
        $modulos = Modulo::all(); // Inicialmente todos, se filtrarán por JavaScript
        return view('anexo03.create', compact('empresas', 'efsrtList', 'programasEstudio', 'modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_programa_estudio' => 'required|exists:programas_estudios,id',
            'id_modulo' => 'required|exists:modulos,id',
            'nro_modulo' => 'required|integer|min:1',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date',
            'horario' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
            'pago_por' => 'nullable|string',
            'movilidad' => 'nullable|string',
            'otros' => 'nullable|string',
            'solo_EFSRT' => 'nullable|boolean',
            'idEmpresa' => 'nullable|exists:empresas,id',
            'detalle_otros' => 'nullable|string',
            'id_EFSRT' => 'nullable|exists:efsrt,id'
        ]);

        Anexo03::create($request->all());

        return redirect()->route('anexo03.index')->with('success', 'Anexo 03 creado correctamente.');
    }

    public function edit(Anexo03 $anexo03)
    {
        $empresas = Empresa::all();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        $programasEstudio = ProgramaEstudio::all();
        $modulos = Modulo::all();
        return view('anexo03.edit', compact('anexo03', 'empresas', 'efsrtList', 'programasEstudio', 'modulos'));
    }

    public function update(Request $request, Anexo03 $anexo03)
    {
        $request->validate([
            'id_programa_estudio' => 'required|exists:programas_estudios,id',
            'id_modulo' => 'required|exists:modulos,id',
            'nro_modulo' => 'required|integer|min:1',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date',
            'horario' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
            'pago_por' => 'nullable|string',
            'movilidad' => 'nullable|string',
            'otros' => 'nullable|string',
            'solo_EFSRT' => 'nullable|boolean',
            'idEmpresa' => 'nullable|exists:empresas,id',
            'detalle_otros' => 'nullable|string',
            'id_EFSRT' => 'nullable|exists:efsrt,id'
        ]);

        $anexo03->update($request->all());

        return redirect()->route('anexo03.index')->with('success', 'Anexo 03 actualizado correctamente.');
    }

    public function destroy(Anexo03 $anexo03)
    {
        $anexo03->delete();
        return redirect()->route('anexo03.index')->with('success', 'Anexo 03 eliminado correctamente.');
    }
}
