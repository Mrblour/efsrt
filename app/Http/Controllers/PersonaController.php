<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Persona;

class PersonaController extends Controller
{
    // Mostrar lista de personas con búsqueda
    public function index(Request $request)
    {
        $query = Persona::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%");
        }

        $personas = $query->get();

        return view('persona.index', compact('personas'));
    }

    // Mostrar formulario de creación
    public function create()
    {
        $programasEstudio = \App\Models\ProgramaEstudio::all();
        return view('persona.create', compact('programasEstudio'));
    }

    // Guardar nueva persona
    public function store(Request $request)
    {
        // Generar 'usuario' a partir del email si no viene en el request
        if (!$request->has('usuario') || empty($request->usuario)) {
            $request->merge(['usuario' => explode('@', $request->email)[0]]);
        }

        $request->validate([
            // Validación de datos de acceso
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_type' => 'required|string|in:admin,coordinador,docente,estudiante',
            
            // Validación de datos personales
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|max:8|unique:personas,dni',
            'correo' => 'required|email|max:255|unique:personas,correo',
            'telefono' => 'required|string|max:15',
            'usuario' => 'required|string|max:50|unique:personas,usuario',
            'fecha_nacimiento' => 'required|date',
            'direccion' => 'nullable|string|max:255',
            'id_tipo_documento' => 'required|integer|exists:tipos_documentos,id',
            
            // Validación condicional para Docente
            'id_programa_docente' => 'required_if:role_type,docente|nullable|integer|exists:programas_estudios,id',
            'especialidad' => 'required_if:role_type,docente|nullable|string|max:80',
            
            // Validación condicional para Estudiante
            'id_programa_estudiante' => 'required_if:role_type,estudiante|nullable|integer|exists:programas_estudios,id',

            // Validación condicional para Coordinador
            'id_programa_coordinador' => 'required_if:role_type,coordinador|nullable|integer|exists:programas_estudios,id',
        ]);

        // Usar transacción para crear Persona y User juntos
        \DB::transaction(function () use ($request) {
            // 1. Crear la Persona
            $persona = Persona::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'usuario' => $request->usuario,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'direccion' => $request->direccion,
                'id_tipo_documento' => $request->id_tipo_documento,
                'password' => bcrypt($request->password), // Usar la contraseña del formulario
            ]);

            // 2. Crear el User vinculado a la Persona
            \App\Models\User::create([
                'name' => $request->nombres . ' ' . $request->apellidos,
                'email' => $request->email, // Usar el email del formulario
                'password' => \Hash::make($request->password), // Usar la contraseña del formulario
                'id_persona' => $persona->id,
                'role_type' => $request->role_type, // Usar el rol seleccionado
            ]);

            // 3. Crear Docente, Estudiante o Coordinador si aplica
            if ($request->role_type === 'docente') {
                \App\Models\Docente::create([
                    'id' => $persona->id,
                    'id_programa' => $request->id_programa_docente,
                    'especialidad' => $request->especialidad,
                ]);
            } elseif ($request->role_type === 'estudiante') {
                \App\Models\Estudiante::create([
                    'id' => $persona->id,
                    'id_programa' => $request->id_programa_estudiante,
                ]);
            } elseif ($request->role_type === 'coordinador') {
                \App\Models\Coordinador::create([
                    'id' => $persona->id,
                    'id_programa' => $request->id_programa_coordinador,
                ]);
            }
        });

        return redirect()->route('persona.index')->with('success', 'Persona y usuario creados correctamente. Ya puede iniciar sesión con su email y contraseña.');
    }

    // Mostrar formulario de edición
    public function edit($id)
    {
        $persona = Persona::with('user')->findOrFail($id);
        $programasEstudio = \App\Models\ProgramaEstudio::all();
        return view('persona.edit', compact('persona', 'programasEstudio'));
    }

    // Actualizar persona existente
    public function update(Request $request, $id)
    {
        $persona = Persona::findOrFail($id);
        $user = \App\Models\User::where('id_persona', $persona->id)->first();

        // Generar 'usuario' a partir del email si no viene en el request
        if (!$request->has('usuario') || empty($request->usuario)) {
            $request->merge(['usuario' => explode('@', $request->email)[0]]);
        }

        $request->validate([
            // Validación de datos de acceso
            'email' => 'required|email|max:255|unique:users,email,' . ($user ? $user->id : 'NULL'),
            'password' => 'nullable|string|min:6|confirmed', // Opcional en edición
            'role_type' => 'required|string|in:admin,coordinador,docente,estudiante',
            
            // Validación de datos personales
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|max:8|unique:personas,dni,' . $persona->id,
            'correo' => 'required|email|max:255|unique:personas,correo,' . $persona->id,
            'telefono' => 'required|string|max:15',
            'usuario' => 'required|string|max:50|unique:personas,usuario,' . $persona->id,
            'fecha_nacimiento' => 'required|date',
            'direccion' => 'nullable|string|max:255',
            'id_tipo_documento' => 'required|integer|exists:tipos_documentos,id',
            
            // Validación condicional para Docente
            'id_programa_docente' => 'required_if:role_type,docente|nullable|integer|exists:programas_estudios,id',
            'especialidad' => 'required_if:role_type,docente|nullable|string|max:80',
            
            // Validación condicional para Estudiante
            'id_programa_estudiante' => 'required_if:role_type,estudiante|nullable|integer|exists:programas_estudios,id',

            // Validación condicional para Coordinador
            'id_programa_coordinador' => 'required_if:role_type,coordinador|nullable|integer|exists:programas_estudios,id',
        ]);

        // Usar transacción para actualizar todo
        \DB::transaction(function () use ($request, $persona) {
            // 1. Actualizar la Persona
            $personaData = [
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'usuario' => $request->usuario,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'direccion' => $request->direccion,
                'id_tipo_documento' => $request->id_tipo_documento,
            ];
            
            // Si hay contraseña nueva, actualizarla
            if ($request->filled('password')) {
                $personaData['password'] = bcrypt($request->password);
            }
            
            $persona->update($personaData);

            // 2. Actualizar el User vinculado
            $user = \App\Models\User::where('id_persona', $persona->id)->first();
            if ($user) {
                $userData = [
                    'name' => $request->nombres . ' ' . $request->apellidos,
                    'email' => $request->email,
                    'role_type' => $request->role_type,
                ];
                
                // Si hay contraseña nueva, actualizarla
                if ($request->filled('password')) {
                    $userData['password'] = \Hash::make($request->password);
                }
                
                $user->update($userData);
            }
            
            // 3. Actualizar/Crear/Eliminar Docente según el rol
            $docente = \App\Models\Docente::find($persona->id);
            if ($request->role_type === 'docente') {
                if ($docente) {
                    $docente->update([
                        'id_programa' => $request->id_programa_docente,
                        'especialidad' => $request->especialidad,
                    ]);
                } else {
                    \App\Models\Docente::create([
                        'id' => $persona->id,
                        'id_programa' => $request->id_programa_docente,
                        'especialidad' => $request->especialidad,
                    ]);
                }
            } else {
                if ($docente) $docente->delete();
            }
            
            // 4. Actualizar/Crear/Eliminar Estudiante según el rol
            $estudiante = \App\Models\Estudiante::find($persona->id);
            if ($request->role_type === 'estudiante') {
                if ($estudiante) {
                    $estudiante->update([
                        'id_programa' => $request->id_programa_estudiante,
                    ]);
                } else {
                    \App\Models\Estudiante::create([
                        'id' => $persona->id,
                        'id_programa' => $request->id_programa_estudiante,
                    ]);
                }
            } else {
                if ($estudiante) $estudiante->delete();
            }

            // 5. Actualizar/Crear/Eliminar Coordinador según el rol
            $coordinador = \App\Models\Coordinador::find($persona->id);
            if ($request->role_type === 'coordinador') {
                if ($coordinador) {
                    $coordinador->update([
                        'id_programa' => $request->id_programa_coordinador,
                    ]);
                } else {
                    \App\Models\Coordinador::create([
                        'id' => $persona->id,
                        'id_programa' => $request->id_programa_coordinador,
                    ]);
                }
            } else {
                if ($coordinador) $coordinador->delete();
            }
        });

        return redirect()->route('persona.index')->with('success', 'Persona y usuario actualizados correctamente.');
    }

    // Eliminar persona
    public function destroy($id)
    {
        $persona = Persona::findOrFail($id);
        
        // Usar transacción para eliminar todo relacionado
        \DB::transaction(function () use ($persona) {
            // 1. Eliminar User asociado (si existe)
            $user = \App\Models\User::where('id_persona', $persona->id)->first();
            if ($user) {
                $user->delete();
            }
            
            // 2. Eliminar Docente (si existe)
            $docente = \App\Models\Docente::find($persona->id);
            if ($docente) {
                $docente->delete();
            }
            
            // 3. Eliminar Estudiante (si existe)
            $estudiante = \App\Models\Estudiante::find($persona->id);
            if ($estudiante) {
                $estudiante->delete();
            }
            
            // 4. Finalmente eliminar la Persona
            $persona->delete();
        });

        return redirect()->route('persona.index')->with('success', 'Persona, usuario y datos relacionados eliminados correctamente.');
    }
}
