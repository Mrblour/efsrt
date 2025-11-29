<?php

namespace App\Http\Controllers;

use App\Models\Anexo05;
use App\Models\Empresa;
use App\Models\EFSRT;
use Illuminate\Http\Request;

class Anexo05Controller extends Controller
{
    public function index()
    {
        // Usamos paginate, ordenamos por ID y cargamos relaciones de forma eficiente
        $anexos = Anexo05::with(['empresa', 'efsrt.estudiante.persona'])
                          ->orderBy('id', 'desc')
                          ->paginate(10);
                          
        return view('anexo05.index', compact('anexos'));
    }

    public function create()
    {
        $anexo05 = new Anexo05(); // Instancia vacía para el formulario
        $empresas = Empresa::orderBy('razon_social')->get();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        return view('anexo05.create', compact('anexo05', 'empresas', 'efsrtList'));
    }

    public function store(Request $request)
    {
        // Reglas de validación corregidas y mejoradas
        $validatedData = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'total_horas' => 'nullable|integer|min:0',
            'idEmpresa' => 'nullable|exists:empresas,id',
            'id_EFSRT' => 'nullable|exists:efsrt,id',
            'horario' => 'nullable|string|max:255',
            'tareas' => 'nullable|string',
            'total_puntaje' => 'nullable|numeric|min:0',
            'fecha_anexo' => 'nullable|date',
            'lugar_anexo' => 'nullable|string|max:255',
            'detalle_otros' => 'nullable|string|max:255',
            'lugar_oficina' => 'nullable|boolean', // Regla booleana
            'lugar_laboratorio' => 'nullable|boolean',
            'lugar_almacen' => 'nullable|boolean',
            'lugar_campo' => 'nullable|boolean',
            'lugar_otros' => 'nullable|boolean',
            'lugar_taller' => 'nullable|boolean',
        ]);

        // Lógica correcta para manejar checkboxes (los no marcados no se envían)
        $validatedData['lugar_oficina'] = $request->has('lugar_oficina');
        $validatedData['lugar_laboratorio'] = $request->has('lugar_laboratorio');
        $validatedData['lugar_almacen'] = $request->has('lugar_almacen');
        $validatedData['lugar_campo'] = $request->has('lugar_campo');
        $validatedData['lugar_otros'] = $request->has('lugar_otros');
        $validatedData['lugar_taller'] = $request->has('lugar_taller');

        Anexo05::create($validatedData);

        return redirect()->route('anexo05.index')->with('success', 'Anexo 05 creado correctamente.');
    }

    public function edit(Anexo05 $anexo05)
    {
        $empresas = Empresa::orderBy('razon_social')->get();
        $efsrtList = EFSRT::with('estudiante.persona')->get();
        return view('anexo05.edit', compact('anexo05', 'empresas', 'efsrtList'));
    }

    public function update(Request $request, Anexo05 $anexo05)
    {
        // Las reglas y la lógica son las mismas que en 'store'
        $validatedData = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'total_horas' => 'nullable|integer|min:0',
            'idEmpresa' => 'nullable|exists:empresas,id',
            'id_EFSRT' => 'nullable|exists:efsrt,id',
            'horario' => 'nullable|string|max:255',
            'tareas' => 'nullable|string',
            'total_puntaje' => 'nullable|numeric|min:0',
            'fecha_anexo' => 'nullable|date',
            'lugar_anexo' => 'nullable|string|max:255',
            'detalle_otros' => 'nullable|string|max:255',
            'lugar_oficina' => 'nullable|boolean',
            'lugar_laboratorio' => 'nullable|boolean',
            'lugar_almacen' => 'nullable|boolean',
            'lugar_campo' => 'nullable|boolean',
            'lugar_otros' => 'nullable|boolean',
            'lugar_taller' => 'nullable|boolean',
        ]);
        
        $validatedData['lugar_oficina'] = $request->has('lugar_oficina');
        $validatedData['lugar_laboratorio'] = $request->has('lugar_laboratorio');
        $validatedData['lugar_almacen'] = $request->has('lugar_almacen');
        $validatedData['lugar_campo'] = $request->has('lugar_campo');
        $validatedData['lugar_otros'] = $request->has('lugar_otros');
        $validatedData['lugar_taller'] = $request->has('lugar_taller');
        
        $anexo05->update($validatedData);

        return redirect()->route('anexo05.index')->with('success', 'Anexo 05 actualizado correctamente.');
    }

    public function destroy(Anexo05 $anexo05)
    {
        $anexo05->delete();
        return redirect()->route('anexo05.index')->with('success', 'Anexo 05 eliminado correctamente.');
    }
}