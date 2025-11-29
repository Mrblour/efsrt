<?php

namespace App\Http\Controllers;

use App\Models\Coordinador;
use App\Models\Persona;
use App\Models\User;
use App\Models\ProgramaEstudio;
use App\Models\TipoDocumento;
use App\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoordinadorController extends Controller
{
    protected $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function index(Request $request)
    {
        $query = Coordinador::with(['persona', 'programaEstudio']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('persona', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            })->orWhereHas('programaEstudio', function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        $coordinadores = $query->get();
        $programas = ProgramaEstudio::all(); // Para el modal de crear
        
        return view('coordinadors.index', compact('coordinadores', 'programas'));
    }

    /**
     * Buscar datos de persona por DNI usando ApiService.
     */
    public function searchDni(Request $request)
    {
        $request->validate(['dni' => 'required|digits:8']);
        
        $dni = $request->dni;
        
        // 1. Verificar si ya existe en BD local
        $personaLocal = Persona::where('dni', $dni)->first();
        if ($personaLocal) {
            return response()->json([
                'found_local' => true,
                'message' => 'La persona ya está registrada en el sistema.',
                'data' => $personaLocal
            ]);
        }

        // 2. Consultar API externa
        $result = $this->apiService->consultarDni($dni);
        
        if ($result['success']) {
            return response()->json([
                'found_local' => false,
                'found_api' => true,
                'data' => $result['data']
            ]);
        }

        return response()->json([
            'found_local' => false,
            'found_api' => false,
            'message' => 'DNI no encontrado en la API.'
        ], 404);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dni' => 'required|digits:8',
            'nombres' => 'required|string|max:60',
            'apellidos' => 'required|string|max:60',
            'id_programa' => 'required|exists:programas_estudios,id',
        ]);

        try {
            DB::beginTransaction();

            // 1. Buscar o Crear Persona
            $persona = Persona::where('dni', $request->dni)->first();

            if (!$persona) {
                // Obtener ID de Tipo Documento DNI (asumimos que es 1 o buscamos por nombre)
                $tipoDoc = TipoDocumento::where('nombre', 'like', '%DNI%')->first();
                $idTipoDoc = $tipoDoc ? $tipoDoc->id : 1; // Fallback a 1 si no encuentra

                $persona = Persona::create([
                    'nombres' => $request->nombres,
                    'apellidos' => $request->apellidos,
                    'dni' => $request->dni,
                    'correo' => $request->dni . '@cord', // Correo generado automáticamente si no existe
                    'direccion' => null,
                    'telefono' => null,
                    'fecha_nacimiento' => null,
                    'id_tipo_documento' => $idTipoDoc,
                ]);
            }

            // 2. Verificar si ya es coordinador
            if (Coordinador::where('id', $persona->id)->exists()) {
                return back()->with('error', 'Esta persona ya está registrada como coordinador.');
            }

            // 3. Crear Usuario si no existe
            $user = User::where('id_persona', $persona->id)->first();
            if (!$user) {
                $user = User::create([
                    'name' => $request->nombres, // O usar DNI
                    'email' => $request->dni . '@cord',
                    'password' => Hash::make($request->dni), // Contraseña es el DNI
                    'id_persona' => $persona->id,
                    'role_type' => 'coordinador', // Asumimos que existe este rol o se maneja así
                ]);
            } else {
                // Si ya existe usuario, ¿le cambiamos el rol? 
                // Por seguridad, mejor no tocamos el usuario existente salvo que sea necesario.
                // Podríamos actualizar el rol si el sistema permite múltiples roles o cambio de rol.
                // Por ahora, asumimos que si ya existe, se usa ese usuario.
            }

            // 4. Crear Coordinador
            Coordinador::create([
                'id' => $persona->id,
                'id_programa' => $request->id_programa,
            ]);

            DB::commit();

            return redirect()->route('coordinadors.index')->with('success', 'Coordinador creado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando coordinador: ' . $e->getMessage());
            return back()->with('error', 'Error al crear coordinador: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $coordinador = Coordinador::findOrFail($id);
        $coordinador->delete();
        return redirect()->route('coordinadors.index')->with('success', 'Coordinador eliminado correctamente.');
    }
}
