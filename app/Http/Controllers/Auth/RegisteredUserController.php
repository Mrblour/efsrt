<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Persona;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\TipoDocumento;
use App\Models\ProgramaEstudio;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB; // Para transacciones
use Illuminate\Support\Facades\Log; // Para logging

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $tiposDocumento = TipoDocumento::all();
        $programasEstudio = ProgramaEstudio::all();

        return view('auth.register', compact('tiposDocumento', 'programasEstudio'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            Log::info('Datos recibidos en registro:', $request->all());
            
            return DB::transaction(function () use ($request) {
                // Generar 'name' a partir del email si no viene en el request
                if (!$request->has('name') || empty($request->name)) {
                    $request->merge(['name' => explode('@', $request->email)[0]]);
                }

                $validated = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],

                    // Reglas de validación para la tabla Personas
                    'nombres' => ['required', 'string', 'max:60'],
                    'apellidos' => ['required', 'string', 'max:60'],
                    'dni' => ['required', 'string', 'max:15', 'unique:personas,dni'],
                    'correo_persona' => ['required', 'string', 'email', 'max:100', 'unique:personas,correo'],
                    'direccion' => ['nullable', 'string', 'max:150'],
                    'telefono' => ['nullable', 'string', 'max:15'],
                    'fecha_nacimiento' => ['nullable', 'date'],
                    'id_tipo_documento' => ['required', 'integer', 'exists:tipos_documentos,id'],

                    // Regla para el tipo de rol (Actualizado: 'none' -> 'coordinador')
                    'role_type' => ['required', 'string', 'in:admin,coordinador,docente,estudiante'],

                    // Reglas de validación condicionales para Docente
                    'especialidad' => ['nullable', 'string', 'max:80', 'required_if:role_type,docente'],
                    'id_programa_docente' => ['nullable', 'integer', 'exists:programas_estudios,id', 'required_if:role_type,docente'],

                    // Reglas de validación condicionales para Estudiante
                    'id_programa_estudiante' => ['nullable', 'integer', 'exists:programas_estudios,id', 'required_if:role_type,estudiante'],
                ]);

                Log::info('Validación pasada correctamente');

                // 1. Crear el registro en la tabla 'personas'
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

                Log::info('Persona creada ID: ' . $persona->id);

                // 2. Crear el registro en la tabla 'users' y vincularlo con la persona y el rol
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'id_persona' => $persona->id,
                    'role_type' => $request->role_type,
                ]);

                Log::info('Usuario creado ID: ' . $user->id);

                // 3. Crear el registro en la tabla 'docentes' o 'estudiantes' si aplica
                if ($request->role_type === 'docente') {
                    $docente = Docente::create([
                        'id' => $persona->id,
                        'id_programa' => $request->id_programa_docente,
                        'especialidad' => $request->especialidad,
                    ]);
                    Log::info('Docente creado ID: ' . $docente->id);
                    
                } elseif ($request->role_type === 'estudiante') {
                    $estudiante = Estudiante::create([
                        'id' => $persona->id,
                        'id_programa' => $request->id_programa_estudiante,
                    ]);
                    Log::info('Estudiante creado ID: ' . $estudiante->id);
                }

                event(new Registered($user));

                // Quitamos Auth::login($user);
                // Redirigir al login en lugar del dashboard
                return redirect()->route('login')->with('success', 'Registro exitoso. Ahora puedes iniciar sesión.');
            });
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación en registro: ' . json_encode($e->errors()));
            throw $e; // Laravel manejará automáticamente la redirección con errores
            
        } catch (\Exception $e) {
            Log::error('Error en registro: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }
}
