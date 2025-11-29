<?php

namespace App\Http\Controllers;

use App\Models\EFSRT;
use App\Models\Estudiante;
use App\Models\Modulo;
use App\Models\Docente;
use App\Models\Empresa;
use App\Models\Semestre;
use App\Models\ProgramaEstudio;
use App\Models\PlanEstudio;
use Illuminate\Http\Request;

class EFSRTController extends Controller
{
    public function index()
    {
        $efsrt = EFSRT::with(['estudiante', 'modulo', 'docenteAsesor', 'empresa', 'semestre'])->get();
        return view('efsrt.index', compact('efsrt'));
    }

    public function create()
    {
        $estudiantes = Estudiante::all();
        $programasEstudio = ProgramaEstudio::all();
        $modulos = Modulo::all(); // Inicialmente todos, se filtrarán por JavaScript
        $docentes = Docente::all();
        $empresas = Empresa::all();
        $semestres = Semestre::all();

        return view('efsrt.create', compact('estudiantes', 'programasEstudio', 'modulos', 'docentes', 'empresas', 'semestres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_estudiante' => 'required|exists:estudiantes,id',
            'fecha_registro' => 'required|date',
            'id_modulo' => 'required|exists:modulos,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'id_docente_asesor' => 'nullable|exists:docentes,id',
            'id_empresa' => 'nullable|exists:empresas,id',
            'id_semestre' => 'nullable|exists:semestres,id',
            'anexo3_PDF' => 'nullable|file|mimes:pdf',
            'anexo4_PDF' => 'nullable|file|mimes:pdf',
            'anexo5_PDF' => 'nullable|file|mimes:pdf',
            'fecha_anexo3' => 'nullable|date',
            'fecha_anexo4' => 'nullable|date',
            'fecha_anexo5' => 'nullable|date',
            'codigo_tramite' => 'nullable|string|max:255'
        ]);

        $data = $request->all();

        // Guardar PDFs si existen
        foreach (['anexo3_PDF', 'anexo4_PDF', 'anexo5_PDF'] as $anexo) {
            if ($request->hasFile($anexo)) {
                $data[$anexo] = $request->file($anexo)->store('anexos', 'public');
            }
        }

        EFSRT::create($data);

        return redirect()->route('efsrt.index')->with('success', 'Registro creado correctamente.');
    }

    public function edit($id)
    {
        $efsrt = EFSRT::findOrFail($id);
        $estudiantes = Estudiante::all();
        $programasEstudio = ProgramaEstudio::all();
        $modulos = Modulo::all();
        $docentes = Docente::all();
        $empresas = Empresa::all();
        $semestres = Semestre::all();

        return view('efsrt.edit', compact('efsrt', 'estudiantes', 'programasEstudio', 'modulos', 'docentes', 'empresas', 'semestres'));
    }

    public function update(Request $request, $id)
    {
        $efsrt = EFSRT::findOrFail($id);

        $request->validate([
            'id_estudiante' => 'required|exists:estudiantes,id',
            'fecha_registro' => 'required|date',
            'id_modulo' => 'required|exists:modulos,id',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'id_docente_asesor' => 'nullable|exists:docentes,id',
            'id_empresa' => 'nullable|exists:empresas,id',
            'id_semestre' => 'nullable|exists:semestres,id',
            'anexo3_PDF' => 'nullable|file|mimes:pdf',
            'anexo4_PDF' => 'nullable|file|mimes:pdf',
            'anexo5_PDF' => 'nullable|file|mimes:pdf',
            'fecha_anexo3' => 'nullable|date',
            'fecha_anexo4' => 'nullable|date',
            'fecha_anexo5' => 'nullable|date',
            'codigo_tramite' => 'nullable|string|max:255'
        ]);

        $data = $request->all();

        foreach (['anexo3_PDF', 'anexo4_PDF', 'anexo5_PDF'] as $anexo) {
            if ($request->hasFile($anexo)) {
                $data[$anexo] = $request->file($anexo)->store('anexos', 'public');
            }
        }

        $efsrt->update($data);

        return redirect()->route('efsrt.index')->with('success', 'Registro actualizado correctamente.');
    }

    public function destroy($id)
    {
        $efsrt = EFSRT::findOrFail($id);
        $efsrt->delete();

        return redirect()->route('efsrt.index')->with('success', 'Registro eliminado correctamente.');
    }

    public function eliminarAnexo(Request $request, $id)
    {
        try {
            $efsrt = EFSRT::findOrFail($id);
            $campo = $request->input('campo');
            
            // Validar que el campo sea válido
            if (!in_array($campo, ['anexo3_PDF', 'anexo4_PDF', 'anexo5_PDF'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo de anexo no válido.'
                ], 400);
            }

            // Verificar si el archivo existe
            if ($efsrt->$campo) {
                // Eliminar archivo físico del storage
                $rutaArchivo = storage_path('app/public/' . $efsrt->$campo);
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }

                // Limpiar el campo en la base de datos
                $efsrt->$campo = null;
                
                // También limpiar la fecha correspondiente
                $campoFecha = str_replace('_PDF', '', $campo);
                $campoFecha = 'fecha_' . $campoFecha;
                $efsrt->$campoFecha = null;
                
                $efsrt->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Archivo eliminado correctamente.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el archivo a eliminar.'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener módulos por programa de estudios (AJAX)
     */
    public function getModulosByPrograma(Request $request)
    {
        try {
            $programaId = $request->input('programa_id');
            
            if (!$programaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de programa requerido'
                ], 400);
            }

            // Obtener módulos a través de los planes de estudio del programa
            $modulos = Modulo::whereHas('planEstudio', function($query) use ($programaId) {
                $query->where('id_programa', $programaId)
                      ->where('activo', 1); // Solo planes activos
            })
            ->with('planEstudio.programaEstudio')
            ->orderBy('numero')
            ->orderBy('nombre')
            ->get();

            $modulosFormatted = $modulos->map(function($modulo) {
                return [
                    'id' => $modulo->id,
                    'nombre' => $modulo->nombre,
                    'numero' => $modulo->numero,
                    'horas' => $modulo->horas,
                    'creditos' => $modulo->creditos,
                    'plan_anio' => $modulo->planEstudio->anio ?? null
                ];
            });

            return response()->json([
                'success' => true,
                'modulos' => $modulosFormatted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener módulos: ' . $e->getMessage()
            ], 500);
        }
    }
}
