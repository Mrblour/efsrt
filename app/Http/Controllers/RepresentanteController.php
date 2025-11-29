<?php

namespace App\Http\Controllers;

use App\Models\Representante;
use App\Models\Persona;
use App\Models\Cargo;
use App\Models\TipoDocumento; // Importa el modelo TipoDocumento
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB; // Para transacciones

class RepresentanteController extends Controller
{
    /**
     * Muestra una lista de todos los representantes.
     */
    public function index(Request $request)
    {
        // Carga los representantes con sus relaciones de persona y cargo para evitar el problema N+1
        $query = Representante::with('persona', 'cargo');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($query) use ($search) {
                $query->whereHas('persona', function($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                      ->orWhere('apellidos', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                })->orWhereHas('cargo', function($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%");
                });
            });
        }

        $representantes = $query->get();
        return view('representantes.index', compact('representantes'));
    }

    /**
     * Muestra el formulario para crear un nuevo representante.
     */
    public function create()
    {
        // Obtener IDs de personas que ya son representantes
        $existingRepresentativePersonIds = Representante::pluck('id')->toArray();

        // Obtener personas que NO son ya representantes
        $personas = Persona::whereNotIn('id', $existingRepresentativePersonIds)->get();

        // Obtener todos los cargos disponibles
        $cargos = Cargo::all();

        // Obtener todos los tipos de documento disponibles para la creación de nueva persona
        $tiposDocumento = TipoDocumento::all();

        // Retornar la vista de creación con las personas, cargos y tipos de documento
        return view('representantes.create', compact('personas', 'cargos', 'tiposDocumento'));
    }

    /**
     * Almacena un nuevo representante en la base de datos.
     */
    public function store(Request $request)
    {
        // Iniciar una transacción de base de datos para asegurar la atomicidad
        return DB::transaction(function () use ($request) {
            try {
                // Validación común para el cargo y es_firmante
                $request->validate([
                    'id_cargo' => 'required|integer|exists:cargos,id',
                    'es_firmante' => 'boolean',
                    'creation_type' => 'required|in:existing,new', // Tipo de creación: existente o nueva persona
                ]);

                $personaId = null;

                if ($request->creation_type === 'new') {
                    // Reglas de validación para la nueva persona
                    $request->validate([
                        'nombres' => ['required', 'string', 'max:60'],
                        'apellidos' => ['required', 'string', 'max:60'],
                        'dni' => ['required', 'string', 'max:15', 'unique:personas,dni'],
                        'correo_persona' => ['required', 'string', 'email', 'max:100', 'unique:personas,correo'],
                        'direccion' => ['nullable', 'string', 'max:150'],
                        'telefono' => ['nullable', 'string', 'max:15'],
                        'fecha_nacimiento' => ['nullable', 'date'],
                        'id_tipo_documento' => ['required', 'integer', 'exists:tipos_documentos,id'],
                    ]);

                    // Crear la nueva persona
                    $persona = Persona::create([
                        'nombres' => $request->nombres,
                        'apellidos' => $request->apellidos,
                        'dni' => $request->dni,
                        'correo' => $request->correo_persona,
                        'direccion' => $request->direccion,
                        'telefono' => $request->telefono,
                        'fecha_nacimiento' => $request->fecha_nacimiento,
                        'id_tipo_documento' => $request->id_tipo_documento,
                    ]);
                    $personaId = $persona->id;

                } elseif ($request->creation_type === 'existing') {
                    // Regla de validación para persona existente
                    $request->validate([
                        'id_persona_existente' => 'required|integer|exists:personas,id|unique:representantes,id',
                    ]);
                    $personaId = $request->id_persona_existente;
                }

                // Si no se pudo determinar un personaId, lanzar un error
                if (is_null($personaId)) {
                    throw new \Exception("No se pudo determinar el ID de la persona para el representante.");
                }

                // Verificar que el cargo existe
                $cargo = Cargo::find($request->id_cargo);
                if (!$cargo) {
                    throw new \Exception("El cargo seleccionado no existe.");
                }

                // Crear el representante usando el ID de la persona
                $representante = Representante::create([
                    'id' => $personaId, // El 'id' de Representante es el mismo que el 'id' de Persona
                    'id_cargo' => $request->id_cargo,
                    'es_firmante' => $request->boolean('es_firmante'),
                ]);

                // Verificar que se creó correctamente
                if (!$representante) {
                    throw new \Exception("Error al crear el representante en la base de datos.");
                }

                // Cargar las relaciones para obtener los nombres
                $representante->load('persona', 'cargo');
                
                $personaNombre = $representante->persona->nombres . ' ' . $representante->persona->apellidos;
                $cargoNombre = $representante->cargo->nombre;
                
                $mensaje = "✅ Representante '{$personaNombre}' creado exitosamente con el cargo de '{$cargoNombre}'.";
                
                // Forzar la redirección con código de estado
                return redirect()->route('representantes.index', [], 302)->with('success', $mensaje);

            } catch (ValidationException $e) {
                // Si la validación falla, redirige de vuelta con los errores y los datos de entrada
                return redirect()->back()->withErrors($e->errors())->withInput();
            } catch (\Exception $e) {
                // Manejo de otros errores inesperados
                return redirect()->back()->with('error', 'Error al crear el representante: ' . $e->getMessage())->withInput();
            }
        }); // Fin de la transacción
    }

    /**
     * Muestra el formulario para editar un representante existente.
     * Devuelve una vista parcial para ser cargada en un modal.
     */
    public function edit(Representante $representante)
    {
        // Obtener todas las personas que NO son representantes,
        // excepto la persona que ya está asociada con el representante actual.
        // Nota: En edición, el ID de la persona (que es la PK del representante) no debería cambiar.
        // Este select es más para mostrar la persona asociada si se quisiera.
        $personas = Persona::all(); // En edición, podemos mostrar todas las personas, pero el campo estará deshabilitado.

        $cargos = Cargo::all();

        // Devuelve la vista del formulario de edición
        return view('representantes.edit', compact('representante', 'personas', 'cargos'));
    }

    /**
     * Actualiza un representante existente en la base de datos.
     */
    public function update(Request $request, Representante $representante)
    {
        try {
            // Validar los datos de entrada
            $request->validate([
                // 'id_persona' no se valida como unique aquí porque no debería cambiar la PK
                // Solo se valida que el id_cargo y es_firmante sean correctos
                'id_cargo' => 'required|integer|exists:cargos,id',
                'es_firmante' => 'boolean',
            ]);

            // Actualizar el representante.
            // El 'id' (que es id_persona) no se actualiza porque es la PK y no debe cambiar.
            $representante->update([
                'id_cargo' => $request->id_cargo,
                'es_firmante' => $request->boolean('es_firmante'),
            ]);

            return redirect()->route('representantes.index')->with('success', 'Representante actualizado exitosamente.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al actualizar el representante: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Elimina un representante de la base de datos.
     */
    public function destroy(Representante $representante)
    {
        try {
            $representante->delete();
            return redirect()->route('representantes.index')->with('success', 'Representante eliminado exitosamente.');
        } catch (\Exception $e) {
            if ($e->getCode() == 23000) { // SQLSTATE[23000]: Integrity constraint violation
                return redirect()->back()->with('error', 'No se puede eliminar el representante porque tiene empresas asociadas. Primero, desvincula las empresas.');
            }
            return redirect()->back()->with('error', 'Error al eliminar el representante: ' . $e->getMessage());
        }
    }
}
