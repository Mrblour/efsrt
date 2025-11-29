<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Representante; // Necesario para el formulario de creación
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmpresaController extends Controller
{
    /**
     * Muestra una lista de todas las empresas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Obtener todas las empresas con su representante asociado
        // Usamos with('representante') para cargar la relación y evitar el problema N+1
        $query = Empresa::with('representante.persona', 'representante.cargo');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%")
                  ->orWhere('direccion', 'like', "%{$search}%")
                  ->orWhereHas('representante.persona', function($q) use ($search) {
                      $q->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                  });
        }

        $empresas = $query->get();
        return view('empresas.index', compact('empresas'));
    }

    /**
     * Muestra el formulario para crear una nueva empresa.
     */
    public function create()
    {
        $representantes = Representante::with('persona', 'cargo')->get();
        return view('empresas.create', compact('representantes'));
    }
    /* 
    public function create()
    {
        // Puedes pasar datos adicionales a la vista si son necesarios para el formulario,
        // por ejemplo, una lista de representantes o cargos si los manejas en un select.
        return view('empresas.create');
    } */

    /**
     * Almacena una nueva empresa en la base de datos.
     */
    public function store(Request $request)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                'razon_social' => 'required|string|max:100',
                'direccion' => 'nullable|string|max:150',
                'telefono' => 'nullable|string|max:15',
                'rubro' => 'nullable|string|max:200',
                'id_representante' => 'required|integer|exists:representantes,id', // Asegura que el representante exista
                'RUC' => 'nullable|string|max:11|unique:empresas,RUC', // RUC debe ser único
                'es_activa' => 'nullable|boolean',
            ]);

            // Crear la nueva empresa
            Empresa::create($request->all());

            // Redirigir a la lista de empresas con un mensaje de éxito
            return redirect()->route('empresas.index')->with('success', 'Empresa creada exitosamente.');
        } catch (ValidationException $e) {
            // Si la validación falla, redirige de vuelta con los errores y los datos de entrada
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            // Manejo de otros errores
            return redirect()->back()->with('error', 'Error al crear la empresa: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Muestra los detalles de una empresa específica.
     */
    public function show(Empresa $empresa)
    {
        // Este método se usaría para mostrar una empresa individualmente, si tuvieras una vista 'show'.
        // Por ahora, no es estrictamente necesario para el index y create/store.
        return view('empresas.show', compact('empresa'));
    }

    /**
     * Muestra el formulario para editar una empresa existente.
     */
    public function edit(Empresa $empresa)
    {
        $representantes = Representante::with('persona', 'cargo')->get();
        return view('empresas.edit', compact('empresa', 'representantes'));
    }

    /**
     * Actualiza una empresa existente en la base de datos.
     */
    public function update(Request $request, Empresa $empresa)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                'razon_social' => 'required|string|max:100',
                'direccion' => 'nullable|string|max:150',
                'telefono' => 'nullable|string|max:15',
                'rubro' => 'nullable|string|max:200',
                'id_representante' => 'required|integer|exists:representantes,id',
                'RUC' => 'nullable|string|max:11|unique:empresas,RUC,' . $empresa->id, // RUC debe ser único, excepto para la empresa actual
                'es_activa' => 'nullable|boolean',
            ]);

            // Actualizar la empresa
            $empresa->update($request->all());

            // Redirigir a la lista de empresas con un mensaje de éxito
            return redirect()->route('empresas.index')->with('success', 'Empresa actualizada exitosamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar la empresa: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Elimina una empresa de la base de datos.
     */
    public function destroy(Empresa $empresa)
    {
        try {
            $empresa->delete();
            return redirect()->route('empresas.index')->with('success', 'Empresa eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al eliminar la empresa: ' . $e->getMessage());
        }
    }
    
}
