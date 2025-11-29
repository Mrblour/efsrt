<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EFSRT;
use App\Models\Estudiante;
use App\Models\Docente;
use App\Models\Empresa;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class ReporteController extends Controller
{
    /**
     * Mostrar el menú principal de generación de documentos
     */
    /**
     * Mostrar la lista de estudiantes para generar reportes
     */
    public function index(Request $request)
    {
        // Búsqueda simple
        $query = Estudiante::with(['persona', 'programaEstudio']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('persona', function($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $estudiantes = $query->paginate(10);

        return view('reportes.index', compact('estudiantes'));
    }

    /*
    ██████╗ ██████╗ ███╗   ██╗███████╗████████╗ █████╗ ███╗   ██╗ ██████╗██╗ █████╗ 
    ██╔════╝██╔═══██╗████╗  ██║██╔════╝╚══██╔══╝██╔══██╗████╗  ██║██╔════╝██║██╔══██╗
    ██║     ██║   ██║██╔██╗ ██║███████╗   ██║   ███████║██╔██╗ ██║██║     ██║███████║
    ██║     ██║   ██║██║╚██╗██║╚════██║   ██║   ██╔══██║██║╚██╗██║██║     ██║██╔══██║
    ╚██████╗╚██████╔╝██║ ╚████║███████║   ██║   ██║  ██║██║ ╚████║╚██████╗██║██║  ██║
     ╚═════╝ ╚═════╝ ╚═╝  ╚═══╝╚══════╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═══╝ ╚═════╝╚═╝╚═╝  ╚═╝
    */
    // NOTE: CONSTANCIA EFSRT Generation Engine (DO NOT MODIFY)

    /**
     * Mostrar formulario para seleccionar estudiante (Constancia)
     */
    public function constanciaForm()
    {
        $estudiantes = Estudiante::with(['persona', 'programaEstudio'])->get();
        return view('reportes.partials.constancia-form', compact('estudiantes'));
    }

    /**
     * Generar Constancia de Experiencia Formativa
     */
    public function constanciaEstudiante($id)
    {
        // Obtener TODOS los EFSRTs del estudiante con todas sus relaciones
        $efsrts = EFSRT::with([
            'estudiante.persona',
            'estudiante.programaEstudio',
            'modulo',
            'docenteAsesor.persona',
            'empresa',
            'semestre',
            'anexo05'
        ])->where('id_estudiante', $id)->get();

        // Verificar que el estudiante tenga al menos un EFSRT
        if ($efsrts->isEmpty()) {
            return back()->with('error', 'El estudiante no tiene EFSRT registrados.');
        }

        // Tomar el primer EFSRT para datos básicos del estudiante
        $efsrt = $efsrts->first();

        // Intentar usar la plantilla oficial constancia.docx con el motor robusto
        $templatePath = storage_path('app/templates/constancia.docx');
        $anioActual = date('Y');
        if (file_exists($templatePath) && method_exists($this, 'generarConstanciaUsandoTemplateOficial')) {
            \Log::info('Constancia: usando plantilla oficial constancia.docx (delegado)');
            return $this->generarConstanciaUsandoTemplateOficial($efsrts, $anioActual, $templatePath);
        }

        // Si no existe la plantilla, continuar con el flujo antiguo o fallback
        if (!file_exists($templatePath)) {
            return back()->with('error', 'La plantilla constancia.docx no existe en storage/app/templates/');
        }

        // Debug: Log información sobre los EFSRTs encontrados
        \Log::info('Constancia - EFSRTs encontrados para estudiante ID ' . $id . ': ' . count($efsrts));
        foreach ($efsrts as $index => $efsrtItem) {
            \Log::info('EFSRT ' . ($index + 1) . ': Módulo=' . ($efsrtItem->modulo->nombre ?? 'N/A') . 
                      ', Empresa=' . ($efsrtItem->empresa->razon_social ?? 'N/A') . 
                      ', Horas=' . ($efsrtItem->anexo05->total_horas ?? 'N/A') . 
                      ', Créditos=' . ($efsrtItem->modulo->creditos ?? 'N/A') . 
                      ', Nota=' . ($efsrtItem->anexo05->total_puntaje ?? 'N/A'));
        }

        // Cargar la plantilla
        $templateProcessor = new TemplateProcessor($templatePath);

        // Reemplazar las variables en la plantilla (formato: {variable})
        // Datos del estudiante
        $estudianteNombres = $efsrt->estudiante->persona->nombres ?? 'N/A';
        $estudianteApellidos = $efsrt->estudiante->persona->apellidos ?? 'N/A';
        $estudianteDni = $efsrt->estudiante->persona->dni ?? 'N/A';
        $programaEstudio = $efsrt->estudiante->programaEstudio->nombre ?? 'N/A';
        
        \Log::info('Datos del estudiante para constancia:', [
            'estudiante_nombres' => $estudianteNombres,
            'estudiante_apellidos' => $estudianteApellidos,
            'estudiante_dni' => $estudianteDni,
            'programa_estudio' => $programaEstudio
        ]);
        
        $templateProcessor->setValue('estudiante_nombres', $estudianteNombres);
        $templateProcessor->setValue('estudiante_apellidos', $estudianteApellidos);
        $templateProcessor->setValue('estudiante_dni', $estudianteDni);
        $templateProcessor->setValue('programa_estudio', $programaEstudio);
        
        // Datos de la empresa
        $templateProcessor->setValue('empresa_nombre', $efsrt->empresa->razon_social ?? 'N/A');
        $templateProcessor->setValue('empresa_ruc', $efsrt->empresa->ruc ?? 'N/A');
        $templateProcessor->setValue('empresa_direccion', $efsrt->empresa->direccion ?? 'N/A');
        
        // Datos consolidados de módulos y fechas
        $modulosNombres = [];
        $fechaInicioMasAntigua = null;
        $fechaFinMasReciente = null;
        
        foreach ($efsrts as $efsrtItem) {
            // Recopilar nombres de módulos
            if ($efsrtItem->modulo && $efsrtItem->modulo->nombre) {
                $modulosNombres[] = $efsrtItem->modulo->nombre;
            }
            
            // Encontrar fecha de inicio más antigua
            if ($efsrtItem->fecha_inicio) {
                $fechaInicio = strtotime($efsrtItem->fecha_inicio);
                if (!$fechaInicioMasAntigua || $fechaInicio < $fechaInicioMasAntigua) {
                    $fechaInicioMasAntigua = $fechaInicio;
                }
            }
            
            // Encontrar fecha de fin más reciente
            if ($efsrtItem->fecha_fin) {
                $fechaFin = strtotime($efsrtItem->fecha_fin);
                if (!$fechaFinMasReciente || $fechaFin > $fechaFinMasReciente) {
                    $fechaFinMasReciente = $fechaFin;
                }
            }
        }
        
        // Preparar datos para la plantilla
        $modulosTexto = !empty($modulosNombres) ? implode(', ', array_unique($modulosNombres)) : 'N/A';
        $fechaInicioTexto = $fechaInicioMasAntigua ? date('d/m/Y', $fechaInicioMasAntigua) : 'N/A';
        $fechaFinTexto = $fechaFinMasReciente ? date('d/m/Y', $fechaFinMasReciente) : 'N/A';
        
        $templateProcessor->setValue('modulo_nombre', $modulosTexto);
        $templateProcessor->setValue('fecha_inicio', $fechaInicioTexto);
        $templateProcessor->setValue('fecha_fin', $fechaFinTexto);
        
        // Calcular totales de todos los EFSRTs del estudiante
        $totalHoras = 0;
        $totalCreditos = 0;
        $totalNotas = 0;
        $contadorEfsrts = 0;
        
        foreach ($efsrts as $efsrtItem) {
            // Debug: Log datos de cada EFSRT
            \Log::info('Procesando EFSRT ID: ' . $efsrtItem->id);
            \Log::info('Anexo05 existe: ' . ($efsrtItem->anexo05 ? 'Sí' : 'No'));
            \Log::info('Módulo existe: ' . ($efsrtItem->modulo ? 'Sí' : 'No'));
            
            // Sumar horas del anexo05
            if ($efsrtItem->anexo05) {
                $horas = $efsrtItem->anexo05->total_horas ?? 0;
                \Log::info('Horas encontradas: ' . $horas);
                $totalHoras += $horas;
            }
            
            // Sumar créditos del módulo
            if ($efsrtItem->modulo) {
                $creditos = $efsrtItem->modulo->creditos ?? 0;
                \Log::info('Créditos encontrados: ' . $creditos);
                $totalCreditos += $creditos;
            }
            
            // Sumar notas del anexo05
            if ($efsrtItem->anexo05) {
                $nota = $efsrtItem->anexo05->total_puntaje ?? 0;
                \Log::info('Nota encontrada: ' . $nota);
                if ($nota > 0) {
                    $totalNotas += $nota;
                    $contadorEfsrts++;
                }
            }
        }
        
        // Calcular promedio de notas
        $promedioNotas = $contadorEfsrts > 0 ? round($totalNotas / $contadorEfsrts, 2) : 0;
        
        // Agregar variables de totales
        $templateProcessor->setValue('total_horas', $totalHoras);
        $templateProcessor->setValue('total_creditos', $totalCreditos);
        $templateProcessor->setValue('promedio_notas', $promedioNotas);
        $templateProcessor->setValue('cantidad_efsrts', count($efsrts));
        
        // Debug: Log las variables que se están enviando a la plantilla
        \Log::info('Variables enviadas a plantilla constancia:', [
            'modulo_nombre' => $modulosTexto,
            'total_horas' => $totalHoras,
            'total_creditos' => $totalCreditos,
            'promedio_notas' => $promedioNotas,
            'cantidad_efsrts' => count($efsrts),
            'empresa_nombre' => $efsrt->empresa->razon_social ?? 'N/A'
        ]);
        
        // Agregar información detallada de cada EFSRT (para plantillas que soporten múltiples registros)
        for ($i = 1; $i <= count($efsrts) && $i <= 10; $i++) {
            $efsrtItem = $efsrts->get($i - 1);
            if ($efsrtItem) {
                $templateProcessor->setValue("modulo_nombre_{$i}", $efsrtItem->modulo->nombre ?? 'N/A');
                $templateProcessor->setValue("empresa_nombre_{$i}", $efsrtItem->empresa->razon_social ?? 'N/A');
                $templateProcessor->setValue("horas_{$i}", $efsrtItem->anexo05->total_horas ?? 'N/A');
                $templateProcessor->setValue("creditos_{$i}", $efsrtItem->modulo->creditos ?? 'N/A');
                $templateProcessor->setValue("nota_{$i}", $efsrtItem->anexo05->total_puntaje ?? 'N/A');
                $templateProcessor->setValue("fecha_inicio_{$i}", $efsrtItem->fecha_inicio ? date('d/m/Y', strtotime($efsrtItem->fecha_inicio)) : 'N/A');
                $templateProcessor->setValue("fecha_fin_{$i}", $efsrtItem->fecha_fin ? date('d/m/Y', strtotime($efsrtItem->fecha_fin)) : 'N/A');
            } else {
                // Limpiar variables vacías
                $templateProcessor->setValue("modulo_nombre_{$i}", '');
                $templateProcessor->setValue("empresa_nombre_{$i}", '');
                $templateProcessor->setValue("horas_{$i}", '');
                $templateProcessor->setValue("creditos_{$i}", '');
                $templateProcessor->setValue("nota_{$i}", '');
                $templateProcessor->setValue("fecha_inicio_{$i}", '');
                $templateProcessor->setValue("fecha_fin_{$i}", '');
            }
        }
        
        // CLONACIÓN DE FILAS PARA MÚLTIPLES MÓDULOS (como el padrón)
        try {
            // Preparar datos para clonación de filas
            $datosModulos = [];
            foreach ($efsrts as $efsrtItem) {
                $datosModulos[] = [
                    'modulo_nombre' => $efsrtItem->modulo->nombre ?? 'N/A',
                    'empresa_nombre' => $efsrtItem->empresa->razon_social ?? 'N/A',
                    'horas' => $efsrtItem->anexo05->total_horas ?? '0',
                    'creditos' => $efsrtItem->modulo->creditos ?? '0',
                    'nota' => $efsrtItem->anexo05->total_puntaje ?? '0'
                ];
            }
            
            \Log::info('Datos para clonación de filas:', $datosModulos);
            
            // Intentar clonar filas si el template lo soporta
            if (count($datosModulos) > 0) {
                $templateProcessor->cloneRowAndSetValues('modulo_nombre', $datosModulos);
            }
            
        } catch (\Exception $e) {
            \Log::warning('No se pudo clonar filas en el template: ' . $e->getMessage());
            // Continuar con el método normal si la clonación falla
        }
        
        // Docente asesor
        $docenteNombre = 'N/A';
        if ($efsrt->docenteAsesor && $efsrt->docenteAsesor->persona) {
            $docenteNombre = trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? ''));
        }
        $templateProcessor->setValue('docente_nombre', $docenteNombre);
        
        // Fechas adicionales
        $templateProcessor->setValue('fecha_actual', date('d/m/Y'));
        $templateProcessor->setValue('año', date('Y'));
        $templateProcessor->setValue('mes', date('m'));
        $templateProcessor->setValue('dia', date('d'));

        // Generar el nombre del archivo
        $fileName = 'Constancia_' . $efsrt->estudiante->persona->dni . '_' . date('Ymd') . '.docx';

        // Guardar temporalmente
        $tempFile = storage_path('app/temp/' . $fileName);
        
        // Crear carpeta temp si no existe
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        $templateProcessor->saveAs($tempFile);

        // Descargar el archivo
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /*
    ██████╗  █████╗ ██████╗ ██████╗  ██████╗ ███╗   ██╗
    ██╔══██╗██╔══██╗██╔══██╗██╔══██╗██╔═══██╗████╗  ██║
    ██████╔╝███████║██║  ██║██████╔╝██║   ██║██╔██╗ ██║
    ██╔═══╝ ██╔══██║██║  ██║██╔══██╗██║   ██║██║╚██╗██║
    ██║     ██║  ██║██████╔╝██║  ██║╚██████╔╝██║ ╚████║
    ╚═╝     ╚═╝  ╚═╝╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═══╝
    */
    // NOTE: PADRON EFSRT Generation Engine (DO NOT MODIFY)

    /**
     * Mostrar formulario para configurar el Padrón EFSRT
     */
    public function padronForm()
    {
        $programas = \App\Models\ProgramaEstudio::with('planesEstudio.modulos')->get();
        
        // Obtener todos los módulos agrupados por programa
        $modulosPorPrograma = [];
        foreach ($programas as $programa) {
            $modulosPorPrograma[$programa->id] = [];
            foreach ($programa->planesEstudio as $plan) {
                foreach ($plan->modulos as $modulo) {
                    $modulosPorPrograma[$programa->id][] = $modulo;
                }
            }
        }
        
        return view('reportes.partials.padron-form', compact('programas', 'modulosPorPrograma'));
    }

    /**
     * Generar Padrón de Experiencias Formativas (EFSRT) - ANEXO 01
     */
    public function padronEFSRT(Request $request)
    {
        // Obtener filtros del formulario
        $programaId = $request->input('programa_id');
        $moduloId = $request->input('modulo_id');
        $año = $request->input('año');

        // Obtener los EFSRTs con filtros
        $efsrts = EFSRT::with([
            'estudiante.persona',
            'estudiante.programaEstudio',
            'modulo',
            'docenteAsesor.persona',
            'empresa',
            'semestre',
            'anexo05'
        ])
        ->whereHas('estudiante', function($query) use ($programaId) {
            $query->where('id_programa', $programaId);
        })
        ->where('id_modulo', $moduloId)
        ->whereYear('fecha_registro', $año)
        ->get();

        // Validar que hay datos para generar el reporte
        if ($efsrts->isEmpty()) {
            $programa = \App\Models\ProgramaEstudio::find($programaId);
            $modulo = \App\Models\Modulo::find($moduloId);
            
            $mensaje = "No se encontraron estudiantes con EFSRT para generar el padrón con los siguientes filtros:\n\n";
            $mensaje .= "• Programa: " . ($programa->nombre ?? 'N/A') . "\n";
            $mensaje .= "• Módulo: " . ($modulo->nombre ?? 'N/A') . "\n";
            $mensaje .= "• Año: " . $año . "\n\n";
            $mensaje .= "Posibles causas:\n";
            $mensaje .= "- No hay estudiantes registrados en este programa/módulo\n";
            $mensaje .= "- Los estudiantes no tienen EFSRT asignados\n";
            $mensaje .= "- Los EFSRT no están completos (falta empresa, fechas, etc.)\n\n";
            $mensaje .= "Verifica los datos en el sistema e intenta nuevamente.";
            
            return back()->with('error', $mensaje);
        }

        // Validar que los EFSRT tengan datos completos
        $efsrtsIncompletos = [];
        foreach ($efsrts as $index => $efsrt) {
            $problemas = [];
            
            if (!$efsrt->estudiante || !$efsrt->estudiante->persona) {
                $problemas[] = "Datos de estudiante incompletos";
            }
            if (!$efsrt->empresa) {
                $problemas[] = "Sin empresa asignada";
            }
            if (!$efsrt->docenteAsesor || !$efsrt->docenteAsesor->persona) {
                $problemas[] = "Sin docente asesor";
            }
            if (!$efsrt->modulo) {
                $problemas[] = "Sin módulo asignado";
            }
            
            if (!empty($problemas)) {
                $nombreEstudiante = 'Estudiante ' . ($index + 1);
                if ($efsrt->estudiante && $efsrt->estudiante->persona) {
                    $nombreEstudiante = ($efsrt->estudiante->persona->nombres ?? '') . ' ' . ($efsrt->estudiante->persona->apellidos ?? '');
                }
                $efsrtsIncompletos[] = $nombreEstudiante . ': ' . implode(', ', $problemas);
            }
        }

        // Si hay datos incompletos, mostrar advertencia pero continuar
        if (!empty($efsrtsIncompletos)) {
            \Log::warning('EFSRT con datos incompletos encontrados:', $efsrtsIncompletos);
            
            // Si hay muchos incompletos, mostrar error
            if (count($efsrtsIncompletos) > (count($efsrts) * 0.5)) {
                $mensaje = "Demasiados registros de EFSRT tienen datos incompletos (" . count($efsrtsIncompletos) . " de " . count($efsrts) . "):\n\n";
                $mensaje .= implode("\n", array_slice($efsrtsIncompletos, 0, 5));
                if (count($efsrtsIncompletos) > 5) {
                    $mensaje .= "\n... y " . (count($efsrtsIncompletos) - 5) . " más.";
                }
                $mensaje .= "\n\nPor favor, completa los datos faltantes antes de generar el padrón.";
                
                return back()->with('error', $mensaje);
            }
        }

        // Ruta de la plantilla
        $templatePath = storage_path('app/templates/Anexo01_reporte_template.docx');

        // Verificar si existe la plantilla
        if (!file_exists($templatePath)) {
            return back()->with('error', 'La plantilla Anexo01_reporte_template.docx no existe en storage/app/templates/');
        }

        // Cargar la plantilla
        $templateProcessor = new TemplateProcessor($templatePath);
        
        \Log::info('Usando plantilla oficial: ' . $templatePath);
        
        // Diagnóstico: Analizar qué variables están en la plantilla
        $this->diagnosticarPlantilla($templatePath);
        
        // Preparar variables para reemplazos en diagnóstico XML antes del try
        // Evita avisos de variables indefinidas si se accede en el bloque siguiente
        $programa = \App\Models\ProgramaEstudio::find($programaId);
        $modulo = \App\Models\Modulo::find($moduloId);
        $programaNombre = $programa->nombre ?? 'N/A';
        $moduloNumero = $modulo->numero ?? 'N/A';
        $moduloNombre = $modulo->nombre ?? 'N/A';
        $añoActual = $año ?? date('Y');

        // Debug adicional: Intentar leer el contenido XML directamente
        try {
            $zip = new \ZipArchive();
            if ($zip->open($templatePath) === TRUE) {
                $documentXml = $zip->getFromName('word/document.xml');
                if ($documentXml) {
                    // Normalizar runs fragmentados de Word para que los placeholders no queden partidos
                    // Une secuencias </w:t><w:t> que separan texto continuo
                    $documentXml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $documentXml);
                    // Buscar todas las variables en formato {variable}
                    preg_match_all('/\{([^}]+)\}/', $documentXml, $matches);
                    \Log::info('Variables exactas encontradas:', $matches[1] ?? []);
                    
                    // También buscar fragmentos de variables que puedan estar divididos
                    if (strpos($documentXml, 'programa') !== false) {
                        \Log::info('La palabra "programa" SÍ está en la plantilla');
                    }
                    if (strpos($documentXml, 'modulo') !== false) {
                        \Log::info('La palabra "modulo" SÍ está en la plantilla');
                    }
                }

                // Además, aplicar reemplazos en headers/footers por si los placeholders están allí
                if (method_exists($zip, 'numFiles')) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        $entryName = $stat['name'] ?? '';
                        if (preg_match('/^word\/(header\d*|footer\d*|document)\.xml$/i', $entryName)) {
                            $xml = $zip->getFromIndex($i);
                            if ($xml) {
                                // Normalizar runs fragmentados
                                $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
                                $xml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $xml);
                                // Reemplazos robustos
                                $xml = str_replace('{programa_estudio}', $programaNombre, $xml);
                                $xml = str_replace('programa_estudio', $programaNombre, $xml);
                                $xml = str_replace('{modulo_numero}', $moduloNumero, $xml);
                                $xml = str_replace('modulo_numero', $moduloNumero, $xml);
                                $xml = preg_replace('/\{\s*modulo[_\s]*nombre\s*\}/iu', $moduloNombre, $xml);
                                $xml = preg_replace('/modulo[_\s]*nombre/iu', $moduloNombre, $xml);
                                $xml = str_replace('{año}', $añoActual, $xml);
                                $zip->addFromString($entryName, $xml);
                            }
                        }
                    }
                }

                $zip->close();
            }
        } catch (\Exception $e) {
            \Log::error('Error en diagnóstico adicional: ' . $e->getMessage());
        }

        // Obtener datos para el encabezado
        $primerEfsrt = $efsrts->first();
        $programa = \App\Models\ProgramaEstudio::find($programaId);
        $modulo = \App\Models\Modulo::find($moduloId);

        // Reemplazar variables del encabezado con debugging
        $programaNombre = $programa->nombre ?? 'N/A';
        $moduloNumero = $modulo->numero ?? 'N/A';
        $moduloNombre = $modulo->nombre ?? 'N/A';
        $añoActual = $año ?? date('Y');
        
        \Log::info('Reemplazando variables del encabezado:', [
            'programa_estudio' => $programaNombre,
            'modulo_numero' => $moduloNumero,
            'modulo_nombre' => $moduloNombre,
            'año' => $añoActual
        ]);
        
        // Intentar múltiples formatos de variables
        $variablesToReplace = [
            'programa_estudio' => $programaNombre,
            'modulo_numero' => $moduloNumero,
            'modulo_nombre' => $moduloNombre,
            'año' => $añoActual
        ];
        
        foreach ($variablesToReplace as $variable => $value) {
            $templateProcessor->setValue($variable, $value);
            $templateProcessor->setValue('{' . $variable . '}', $value);
            $templateProcessor->setValue($variable . '', $value);
            \Log::info("Intentando reemplazar variable: {$variable} con valor: {$value}");
        }

        // Procesar datos de estudiantes para la plantilla
        $datosEstudiantes = [];
        foreach ($efsrts as $index => $efsrt) {
            $datosEstudiantes[] = [
                'nro' => $index + 1,
                'estudiante_dni' => $efsrt->estudiante->persona->dni ?? 'N/A',
                'estudiante_apellidos' => $efsrt->estudiante->persona->apellidos ?? '',
                'estudiante_nombres' => $efsrt->estudiante->persona->nombres ?? '',
                'empresa_nombre' => strip_tags($efsrt->empresa->razon_social ?? 'N/A'),
                'docente_nombre' => trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? '')),
                'nota' => $efsrt->anexo05->total_puntaje ?? '',
                'creditos' => $efsrt->modulo->creditos ?? '',
                'hacumu' => $efsrt->anexo05->total_horas ?? ''
            ];
        }

        // Crear una nueva plantilla limpia y usarla
        \Log::info('Creando plantilla limpia para usar template editable');
        return $this->crearYUsarPlantillaLimpia($efsrts, $programa, $modulo, $año, $templatePath);

        // Generar el nombre del archivo
        $fileName = 'Anexo01_Padron_EFSRT_' . date('Ymd') . '.docx';

        // Guardar temporalmente
        $tempFile = storage_path('app/temp/' . $fileName);
        
        // Crear carpeta temp si no existe
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }

        $templateProcessor->saveAs($tempFile);

        // Descargar el archivo
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Mostrar formulario para configurar reporte de docentes
     */
    public function docentesForm()
    {
        $programas = \App\Models\ProgramaEstudio::all();
        return view('reportes.partials.docentes-form', compact('programas'));
    }

    /**
     * Generar Reporte de Docentes (método original - mantener para compatibilidad)
     */
    public function docentesWord()
    {
        try {
            $docentes = Docente::with(['persona', 'programaEstudio'])->get();

            $phpWord = new PhpWord();
            
            // Configurar el documento
            $section = $phpWord->addSection([
                'marginTop' => 800,
                'marginBottom' => 800,
                'marginLeft' => 800,
                'marginRight' => 800
            ]);

            // Estilos
            $titleStyle = ['bold' => true, 'size' => 16, 'name' => 'Arial'];
            $headerStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
            $normalStyle = ['size' => 10, 'name' => 'Arial'];
            $centerAlign = ['alignment' => 'center'];
            
            // Encabezado institucional
            $section->addText('INSTITUTO DE EDUCACIÓN SUPERIOR PÚBLICO "JOSÉ CARLOS MARIÁTEGUI"', $titleStyle, $centerAlign);
            $section->addText('SAMEGUA - MOQUEGUA', $normalStyle, $centerAlign);
            $section->addTextBreak(2);
            
            $section->addText("REPORTE DE DOCENTES", $headerStyle, $centerAlign);
            $section->addText("Fecha: " . date('d/m/Y'), $normalStyle, $centerAlign);
            $section->addTextBreak(2);

            // Crear tabla
            $table = $section->addTable([
                'borderSize' => 6, 
                'borderColor' => '000000', 
                'cellMargin' => 50
            ]);
            
            // Encabezados
            $table->addRow();
            $table->addCell(800)->addText("N°", ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1500)->addText("DNI", ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(3500)->addText("NOMBRES Y APELLIDOS", ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(3000)->addText("PROGRAMA DE ESTUDIOS", ['bold' => true, 'size' => 10], $centerAlign);

            // Datos de docentes
            $contador = 1;
            foreach ($docentes as $docente) {
                $table->addRow();
                
                $dni = $docente->persona->dni ?? 'N/A';
                $nombres = trim(($docente->persona->nombres ?? '') . ' ' . ($docente->persona->apellidos ?? ''));
                $programa = $docente->programaEstudio->nombre ?? 'N/A';
                
                $table->addCell(800)->addText($contador++, ['size' => 10], $centerAlign);
                $table->addCell(1500)->addText($dni, ['size' => 10], $centerAlign);
                $table->addCell(3500)->addText($nombres, ['size' => 10]);
                $table->addCell(3000)->addText($programa, ['size' => 10]);
            }

        // Generar el archivo usando el mismo método que funciona
        $fileName = 'Reporte_Docentes_' . date('Ymd') . '.docx';
        $tempFile = storage_path('app/temp/' . $fileName);
        
        // Crear carpeta temp si no existe
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0777, true);
        }
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);
        
            \Log::info('Reporte de docentes generado exitosamente: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error generando reporte de docentes: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el reporte de docentes: ' . $e->getMessage());
        }
    }

    /**
     * Generar constancia usando plantilla oficial constancia.docx
     * Soporta variables de encabezado en formato {variable} y filas de tabla con ${variable}
     */
    private function generarConstanciaUsandoTemplateOficial($efsrts, $año, $templatePath)
    {
        try {
            // Hacer una copia temporal del template para normalizar placeholders {var} -> ${var}
            $workingPath = storage_path('app/templates/constancia_work.docx');
            copy($templatePath, $workingPath);

            $zip = new \ZipArchive();
            if ($zip->open($workingPath) === TRUE) {
                // Normalizar y convertir {var} a ${var} en todos los XML bajo word/
                $toProcess = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $stat = $zip->statIndex($i);
                    $name = $stat['name'] ?? '';
                    if (preg_match('/^word\/.*\\.xml$/i', $name) || preg_match('/^word\/.*\.xml$/i', $name)) {
                        $toProcess[] = $name;
                    }
                }

                $curlyVars = [
                    // nombres correctos
                    'estudiante_apellidos', 'estudiante_nombres', 'estudiante_dni',
                    'programa_estudio', 'dia', 'mes', 'año',
                    // variantes comunes mal escritas en plantillas
                    'estudiantes_apellidos', 'estudiantes_nombres'
                ];

                foreach ($toProcess as $entry) {
                    $xml = $zip->getFromName($entry);
                    if ($xml === false) { continue; }
                    // Unir runs y reemplazar NBSP
                    $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
                    $xml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $xml);
                    $xml = str_replace("\xC2\xA0", ' ', $xml);
                    // Convertir {var} -> ${var} para variables conocidas o variantes
                    foreach ($curlyVars as $v) {
                        $xml = preg_replace('/\{\s*' . preg_quote($v, '/') . '\s*\}/u', '${' . $v . '}', $xml);
                    }
                    // Conversión general de cualquier {identificador_simple} a ${identificador_simple}
                    // Identificador: letras, números, guion bajo, incluye unicode (u)
                    $xml = preg_replace('/\{\s*([\p{L}0-9_]+)\s*\}/u', '${$1}', $xml);
                    $zip->addFromString($entry, $xml);
                }
                $zip->close();
            }

            // Datos base
            $base = $efsrts->first();
            $apellidos = $base->estudiante->persona->apellidos ?? '';
            $nombres = $base->estudiante->persona->nombres ?? '';
            $dni = $base->estudiante->persona->dni ?? 'N/A';
            $programa = $base->estudiante->programaEstudio->nombre ?? 'N/A';

            // Pase Fallback: reemplazar directamente llaves {var} en XML por valores
            $zip2 = new \ZipArchive();
            if ($zip2->open($workingPath) === TRUE) {
                for ($i = 0; $i < $zip2->numFiles; $i++) {
                    $stat = $zip2->statIndex($i); $entry = $stat['name'] ?? '';
                    if (preg_match('/^word\/.*\.xml$/i', $entry)) {
                        $xml = $zip2->getFromIndex($i);
                        if ($xml) {
                            // normalización rápida
                            $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
                            $xml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $xml);
                            $xml = str_replace("\xC2\xA0", ' ', $xml);
                            // reemplazos directos de llaves y de ${var}
                            $reps = [
                                '{estudiante_apellidos}' => $apellidos,
                                '{estudiante_nombres}' => $nombres,
                                '{estudiantes_apellidos}' => $apellidos,
                                '{estudiantes_nombres}' => $nombres,
                                '{estudiante_dni}' => $dni,
                                '{programa_estudio}' => $programa,
                                '{dia}' => date('d'),
                                '{mes}' => date('m'),
                                '{año}' => (string)$año,
                                '${estudiante_apellidos}' => $apellidos,
                                '${estudiante_nombres}' => $nombres,
                                '${estudiantes_apellidos}' => $apellidos,
                                '${estudiantes_nombres}' => $nombres,
                                '${estudiante_dni}' => $dni,
                                '${programa_estudio}' => $programa,
                                '${dia}' => date('d'),
                                '${mes}' => date('m'),
                                '${año}' => (string)$año,
                            ];
                            $xml = strtr($xml, $reps);
                            // Fallbacks amplios dentro de { … } que contengan el identificador, tolerando espacios
                            $xml = preg_replace('/\{[^}]*estudiante[_\s]*apellidos[^}]*\}/iu', $apellidos, $xml);
                            $xml = preg_replace('/\{[^}]*estudiante[_\s]*nombres[^}]*\}/iu', $nombres, $xml);
                            $xml = preg_replace('/\{[^}]*programa[_\s]*estudio[^}]*\}/iu', $programa, $xml);
                            $xml = preg_replace('/\{[^}]*estudiante[_\s]*dni[^}]*\}/iu', $dni, $xml);
                            // Reemplazo genérico para ${identificador} conocido por TemplateProcessor
                            $xml = preg_replace_callback('/\$\{(estudiante_apellidos|estudiante_nombres|estudiantes_apellidos|estudiantes_nombres|estudiante_dni|programa_estudio|dia|mes|año)\}/u', function($m) use ($apellidos,$nombres,$dni,$programa,$año){
                                $map = [
                                    'estudiante_apellidos' => $apellidos,
                                    'estudiante_nombres' => $nombres,
                                    'estudiantes_apellidos' => $apellidos,
                                    'estudiantes_nombres' => $nombres,
                                    'estudiante_dni' => $dni,
                                    'programa_estudio' => $programa,
                                    'dia' => date('d'),
                                    'mes' => date('m'),
                                    'año' => (string)$año,
                                ];
                                return $map[$m[1]] ?? '';
                            }, $xml);
                            // Limpiar '$' residuales delante de números (p.ej. 'de $$11' -> 'de 11')
                            $xml = preg_replace('/\$+([0-9]{1,4})/', '$1', $xml);
                            $zip2->addFromString($entry, $xml);
                        }
                    }
                }
                $zip2->close();
            }

            // Diagnóstico: listar placeholders restantes en document.xml
            try {
                $zipDiag = new \ZipArchive();
                if ($zipDiag->open($workingPath) === TRUE) {
                    $docXml = $zipDiag->getFromName('word/document.xml');
                    if ($docXml) {
                        // Buscar tokens con llaves o con ${}
                        preg_match_all('/\{[^}]+\}/u', $docXml, $m1);
                        preg_match_all('/\$\{[^}]+\}/u', $docXml, $m2);
                        \Log::info('Constancia DIAG - tokens {..} restantes:', $m1[0] ?? []);
                        \Log::info('Constancia DIAG - tokens ${..} restantes:', $m2[0] ?? []);
                        // Snippet alrededor de la frase Que, Sr(a):
                        if (preg_match('/Que,.*?detalle:/su', $docXml, $m3)) {
                            \Log::info('Constancia DIAG - snippet frase Que, Sr(a):', [mb_substr($m3[0], 0, 500)]);
                        }
                    }
                    $zipDiag->close();
                }
            } catch (\Exception $e) { /* no-op */ }

            // Usar TemplateProcessor sobre el archivo normalizado (ya con reemplazos directos aplicados)
            $template = new TemplateProcessor($workingPath);

            // Fechas (numéricas por simplicidad)
            $template->setValue('estudiante_apellidos', $apellidos);
            $template->setValue('estudiante_nombres', $nombres);
            $template->setValue('estudiante_dni', $dni);
            $template->setValue('programa_estudio', $programa);
            $template->setValue('dia', date('d'));
            $template->setValue('mes', date('m'));
            $template->setValue('año', $año);

            // Asignar también variantes por si quedaron ${estudiantes_*}
            $template->setValue('estudiantes_apellidos', $apellidos);
            $template->setValue('estudiantes_nombres', $nombres);

            // Filas de módulos para la tabla (${modulo_nombre}, ${empresa_nombre}, ${horas}, ${creditos}, ${nota})
            $rows = [];
            foreach ($efsrts as $it) {
                $rows[] = [
                    'modulo_nombre' => $it->modulo->nombre ?? 'N/A',
                    'empresa_nombre' => $it->empresa->razon_social ?? 'N/A',
                    'horas' => $it->anexo05->total_horas ?? '0',
                    'creditos' => $it->modulo->creditos ?? '0',
                    'nota' => $it->anexo05->total_puntaje ?? '0',
                ];
            }
            if (count($rows) > 0) {
                $template->cloneRowAndSetValues('modulo_nombre', $rows);
            }

            // Exportar
            $fileName = 'Constancia_EFSRT_' . $dni . '_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            if (!file_exists(storage_path('app/temp'))) { mkdir(storage_path('app/temp'), 0777, true); }
            $template->saveAs($tempFile);

            // Post-procesado: eliminar '$' residuales antes de números (p. ej. notas/horas)
            try {
                $zipOut = new \ZipArchive();
                if ($zipOut->open($tempFile) === TRUE) {
                    for ($i = 0; $i < $zipOut->numFiles; $i++) {
                        $stat = $zipOut->statIndex($i); $entry = $stat['name'] ?? '';
                        if (preg_match('/^word\/.*\.xml$/i', $entry)) {
                            $xml = $zipOut->getFromIndex($i);
                            if ($xml) {
                                // Unir runs, quitar NBSP y limpiar '$' delante de números
                                $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
                                $xml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $xml);
                                $xml = str_replace("\xC2\xA0", ' ', $xml);
                                $xml = preg_replace('/\$+\s*([0-9]+(?:[\.,][0-9]+)?)/', '$1', $xml);
                                $zipOut->addFromString($entry, $xml);
                            }
                        }
                    }
                    $zipOut->close();
                }
            } catch (\Exception $e) { /* no-op */ }

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error en generarConstanciaUsandoTemplateOficial: ' . $e->getMessage());
            return $this->generarConstanciaDesdeCero($efsrts, $año);
        }
    }

    /**
     * Generar Reporte de Docentes con filtros
     */
    public function generarReporteDocentes(Request $request)
    {
        try {
            $programaId = $request->input('programa_id');
            $tipo = $request->input('tipo', 'completo');
            $año = $request->input('año', date('Y'));

            // Construir query con filtros
            $query = Docente::with(['persona', 'programaEstudio']);
            
            // Aplicar filtro por programa si se selecciona
            if ($programaId) {
                $query->where('id_programa', $programaId);
            }

            $docentes = $query->get();

            // Verificar que haya datos
            if ($docentes->isEmpty()) {
                $programa = null;
                if ($programaId) {
                    $programa = \App\Models\ProgramaEstudio::find($programaId);
                }
                
                $mensaje = "No se encontraron docentes para generar el reporte.\n\n";
                
                if ($programaId && $programa) {
                    $mensaje .= "Filtro aplicado:\n";
                    $mensaje .= "• Programa: " . $programa->nombre . "\n\n";
                    $mensaje .= "Posibles causas:\n";
                    $mensaje .= "- No hay docentes asignados a este programa\n";
                    $mensaje .= "- Los docentes no están correctamente vinculados al programa\n\n";
                    $mensaje .= "Sugerencias:\n";
                    $mensaje .= "- Verifica que haya docentes registrados en este programa\n";
                    $mensaje .= "- Intenta generar el reporte de 'Todos los programas'\n";
                } else {
                    $mensaje .= "No hay docentes registrados en el sistema.\n\n";
                    $mensaje .= "Para generar el reporte necesitas:\n";
                    $mensaje .= "- Al menos un docente registrado\n";
                    $mensaje .= "- Docentes con datos de persona completos\n";
                    $mensaje .= "- Docentes asignados a programas de estudio\n";
                }
                
                \Log::info('Reporte de docentes: No hay datos para programa_id=' . $programaId);
                \Log::info('Mensaje de error: ' . $mensaje);
                
                return back()->with('error', $mensaje);
            }

            return $this->generarReporteDocentesDesdeCero($docentes, $programaId, $tipo, $año);

        } catch (\Exception $e) {
            \Log::error('Error generando reporte de docentes: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Generar reporte de docentes desde cero con filtros
     */
    private function generarReporteDocentesDesdeCero($docentes, $programaId, $tipo, $año)
    {
        try {
            $phpWord = new PhpWord();
            
            // Configurar el documento con encabezados y pies de página
            $section = $phpWord->addSection([
                'marginTop' => 1200,
                'marginBottom' => 1200,
                'marginLeft' => 800,
                'marginRight' => 800,
                'headerHeight' => 600,
                'footerHeight' => 400
            ]);

            // Agregar encabezado
            $header = $section->addHeader();
            
            // Encabezado completo centrado para reportes (tamaños más pequeños)
            $header->addText('INSTITUTO DE EDUCACION SUPERIOR PÚBLICO "JOSE CARLOS MARIATEGUI"', 
                ['bold' => true, 'size' => 9, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('SAMEGUA - MOQUEGUA', 
                ['bold' => true, 'size' => 7, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('Autorización de Funcionamiento R.S. Nº 131-83-ED. Revalidado con R.D. Nº 247-05-ED', 
                ['size' => 6, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('LICENCIADO R.M. N° 577-2019-MINEDU y R.M. N° 655-2024-MINEDU', 
                ['bold' => true, 'size' => 6, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('1975-2025. BODAS DE ORO. "50 años formando profesionales técnicos"', 
                ['bold' => true, 'size' => 6, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('"Año de la recuperación y consolidación de la economía peruana"', 
                ['italic' => true, 'size' => 5, 'name' => 'Arial'], 
                ['alignment' => 'center']);

            // Agregar pie de página
            $footer = $section->addFooter();
            
            // Pie de página simple para reportes
            $footer->addText('Av. Ejército 502 - Samegua - Moquegua | Teléfono: (053) 463-078', 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $footer->addPreserveText('Reporte generado el ' . date('d/m/Y H:i') . ' - Página {PAGE}', 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);

            // Estilos
            $titleStyle = ['bold' => true, 'size' => 16, 'name' => 'Arial'];
            $headerStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
            $normalStyle = ['size' => 10, 'name' => 'Arial'];
            $centerAlign = ['alignment' => 'center'];
            
            // Ya no duplicar el encabezado en el cuerpo (ya está en el header)
            $section->addTextBreak(1);
            
            // Título del reporte
            $tituloReporte = "REPORTE DE DOCENTES";
            if ($programaId) {
                $programa = \App\Models\ProgramaEstudio::find($programaId);
                $tituloReporte .= " - " . strtoupper($programa->nombre ?? 'PROGRAMA');
            }
            
            $section->addText($tituloReporte, $headerStyle, $centerAlign);
            $section->addText("Año: {$año} - Fecha: " . date('d/m/Y'), $normalStyle, $centerAlign);
            $section->addTextBreak(2);

            // Crear tabla
            $table = $section->addTable([
                'borderSize' => 6, 
                'borderColor' => '000000', 
                'cellMargin' => 50
            ]);
            
            // Encabezados
            $table->addRow();
            $table->addCell(800)->addText("N°", ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1500)->addText("DNI", ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(3500)->addText("NOMBRES Y APELLIDOS", ['bold' => true, 'size' => 10], $centerAlign);
            
            if (!$programaId) {
                // Si es reporte general, mostrar programa
                $table->addCell(3000)->addText("PROGRAMA DE ESTUDIOS", ['bold' => true, 'size' => 10], $centerAlign);
            } else {
                // Si es por programa específico, mostrar más detalles
                $table->addCell(1500)->addText("TELÉFONO", ['bold' => true, 'size' => 10], $centerAlign);
                $table->addCell(2000)->addText("EMAIL", ['bold' => true, 'size' => 10], $centerAlign);
            }

            // Datos de docentes
            $contador = 1;
            foreach ($docentes as $docente) {
                $table->addRow();
                
                $dni = $docente->persona->dni ?? 'N/A';
                $nombres = trim(($docente->persona->nombres ?? '') . ' ' . ($docente->persona->apellidos ?? ''));
                $programa = $docente->programaEstudio->nombre ?? 'N/A';
                $telefono = $docente->persona->telefono ?? 'N/A';
                $email = $docente->persona->email ?? 'N/A';
                
                $table->addCell(800)->addText($contador++, ['size' => 10], $centerAlign);
                $table->addCell(1500)->addText($dni, ['size' => 10], $centerAlign);
                $table->addCell(3500)->addText($nombres, ['size' => 10]);
                
                if (!$programaId) {
                    $table->addCell(3000)->addText($programa, ['size' => 10]);
                } else {
                    $table->addCell(1500)->addText($telefono, ['size' => 10], $centerAlign);
                    $table->addCell(2000)->addText($email, ['size' => 9]);
                }
            }

            // Resumen al final
            $section->addTextBreak(2);
            $section->addText("RESUMEN:", ['bold' => true, 'size' => 11]);
            $section->addText("Total de docentes: " . $docentes->count(), $normalStyle);
            
            if ($programaId) {
                $programa = \App\Models\ProgramaEstudio::find($programaId);
                $section->addText("Programa: " . ($programa->nombre ?? 'N/A'), $normalStyle);
            }

            // Generar el archivo
            $sufijo = $programaId ? '_' . str_replace(' ', '_', $programa->nombre ?? 'Programa') : '_Todos';
            $fileName = 'Reporte_Docentes' . $sufijo . '_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            
            \Log::info('Reporte de docentes generado exitosamente: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error generando reporte de docentes desde cero: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el reporte: ' . $e->getMessage());
        }
    }

    /**
     * Método alternativo para generar padrón sin clonación de filas
     */
    private function padronEFSRTAlternativo($efsrts, $templateProcessor, $programa, $modulo, $año, $templatePath)
    {
        try {
            // Primero reemplazar variables del encabezado (ya se hizo antes, pero por seguridad)
            $programaNombre = $programa->nombre ?? 'N/A';
            $moduloNumero = $modulo->numero ?? 'N/A';
            $moduloNombre = $modulo->nombre ?? 'N/A';
            $añoActual = $año ?? date('Y');
            
            // Intentar múltiples formatos para variables fragmentadas
            $templateProcessor->setValue('programa_estudio', $programaNombre);
            $templateProcessor->setValue('modulo_numero', $moduloNumero);  
            $templateProcessor->setValue('modulo_nombre', $moduloNombre);
            $templateProcessor->setValue('año', $añoActual);
            
            // También intentar sin llaves por si están fragmentadas
            $templateProcessor->setValue('programa', $programaNombre);
            $templateProcessor->setValue('modulo', $moduloNombre);
            
            // Método más agresivo: usar setComplexValue para variables fragmentadas
            try {
                // Obtener el XML y reemplazar manualmente las variables fragmentadas
                $zip = new \ZipArchive();
                if ($zip->open($templatePath) === TRUE) {
                    $documentXml = $zip->getFromName('word/document.xml');
                    if ($documentXml) {
                        // Reemplazar variables fragmentadas usando regex
                        $documentXml = preg_replace('/\{programa_estudio\}/', $programaNombre, $documentXml);
                        $documentXml = preg_replace('/\{modulo_numero\}/', $moduloNumero, $documentXml);
                        $documentXml = preg_replace('/\{modulo_nombre\}/', $moduloNombre, $documentXml);
                        
                        // También buscar patrones fragmentados
                        $documentXml = str_replace('programa_estudio', $programaNombre, $documentXml);
                        $documentXml = str_replace('modulo_numero', $moduloNumero, $documentXml);
                        $documentXml = str_replace('modulo_nombre', $moduloNombre, $documentXml);
                        
                        // Guardar el XML modificado de vuelta
                        $zip->addFromString('word/document.xml', $documentXml);
                    }
                    $zip->close();
                }
            } catch (\Exception $e) {
                \Log::warning('No se pudo usar método agresivo: ' . $e->getMessage());
            }
            
            \Log::info('Reemplazando variables en método alternativo:', [
                'programa' => $programaNombre,
                'modulo_numero' => $moduloNumero,
                'modulo_nombre' => $moduloNombre,
                'año' => $añoActual
            ]);
            
            // Intentar reemplazar variables individuales (máximo 50 estudiantes)
            $maxEstudiantes = min($efsrts->count(), 50);
            
            for ($i = 1; $i <= $maxEstudiantes; $i++) {
                $efsrt = $efsrts->get($i - 1);
                
                if ($efsrt) {
                    // Limpiar y validar datos
                    $dni = $efsrt->estudiante->persona->dni ?? 'N/A';
                    $apellidos = $efsrt->estudiante->persona->apellidos ?? '';
                    $nombres = $efsrt->estudiante->persona->nombres ?? '';
                    $empresaNombre = strip_tags($efsrt->empresa->razon_social ?? 'N/A');
                    $docenteNombre = trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? ''));

                    // Obtener datos del Anexo 05
                    $nota = $efsrt->anexo05->total_puntaje ?? '';
                    $creditos = $efsrt->modulo->creditos ?? '';
                    $horasAcum = $efsrt->anexo05->total_horas ?? '';

                    // Reemplazar variables con formato {variable1}, {variable2}, etc.
                    $templateProcessor->setValue("nro{$i}", $i);
                    $templateProcessor->setValue("estudiante_dni{$i}", $dni);
                    $templateProcessor->setValue("estudiante_apellidos{$i}", $apellidos);
                    $templateProcessor->setValue("estudiante_nombres{$i}", $nombres);
                    $templateProcessor->setValue("empresa_nombre{$i}", $empresaNombre);
                    $templateProcessor->setValue("docente_nombre{$i}", $docenteNombre);
                    $templateProcessor->setValue("nota{$i}", $nota);
                    $templateProcessor->setValue("creditos{$i}", $creditos);
                    $templateProcessor->setValue("hacumu{$i}", $horasAcum);
                    
                    // También intentar sin números para la primera fila
                    if ($i == 1) {
                        $templateProcessor->setValue("#", $i);
                        $templateProcessor->setValue("estudiante_dni", $dni);
                        $templateProcessor->setValue("estudiante_apellidos", $apellidos);
                        $templateProcessor->setValue("estudiante_nombres", $nombres);
                        $templateProcessor->setValue("empresa_nombre", $empresaNombre);
                        $templateProcessor->setValue("docente_nombre", $docenteNombre);
                        $templateProcessor->setValue("nota", $nota);
                        $templateProcessor->setValue("creditos", $creditos);
                        $templateProcessor->setValue("hacumu", $horasAcum);
                    }
                } else {
                    // Limpiar variables vacías
                    $templateProcessor->setValue("nro{$i}", '');
                    $templateProcessor->setValue("estudiante_dni{$i}", '');
                    $templateProcessor->setValue("estudiante_apellidos{$i}", '');
                    $templateProcessor->setValue("estudiante_nombres{$i}", '');
                    $templateProcessor->setValue("empresa_nombre{$i}", '');
                    $templateProcessor->setValue("docente_nombre{$i}", '');
                    $templateProcessor->setValue("nota{$i}", '');
                    $templateProcessor->setValue("creditos{$i}", '');
                    $templateProcessor->setValue("hacumu{$i}", '');
                }
            }

            // Generar el archivo
            $fileName = 'Anexo01_Padron_EFSRT_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }

            $templateProcessor->saveAs($tempFile);
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error en método alternativo: ' . $e->getMessage() . '. Contacta al administrador del sistema.');
        }
    }

    /**
     * Crear plantilla limpia basada en el template actual y usarla
     */
    private function crearYUsarPlantillaLimpia($efsrts, $programa, $modulo, $año, $templatePath)
    {
        try {
            // Crear una copia del template para trabajar
            $templateLimpio = storage_path('app/templates/Anexo01_template_limpio.docx');
            copy($templatePath, $templateLimpio);
            
            // Leer el template y limpiar variables fragmentadas
            $zip = new \ZipArchive();
            if ($zip->open($templateLimpio) === TRUE) {
                $documentXml = $zip->getFromName('word/document.xml');
                if ($documentXml) {
                    // Normalizar runs fragmentados y NBSP para que los placeholders no queden partidos ni con espacios duros
                    $documentXml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $documentXml);
                    $documentXml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $documentXml);
                    // NBSP (U+00A0) a espacio normal
                    $documentXml = str_replace("\xC2\xA0", ' ', $documentXml);
                    
                    // Datos reales para reemplazar
                    $programaNombre = $programa->nombre ?? 'N/A';
                    $moduloNumero = $modulo->numero ?? 'N/A';
                    $moduloNombre = $modulo->nombre ?? 'N/A';
                    $añoActual = $año ?? date('Y');
                    
                    // Reemplazar variables fragmentadas directamente en el XML
                    // Quitar llaves sobrantes que aparecen en el documento
                    $documentXml = str_replace('{' . $programaNombre . '}', $programaNombre, $documentXml);
                    $documentXml = str_replace('{' . $moduloNumero . '}', $moduloNumero, $documentXml);
                    $documentXml = str_replace('{programa_estudio}', $programaNombre, $documentXml);
                    $documentXml = str_replace('programa_estudio', $programaNombre, $documentXml);
                    $documentXml = str_replace('{modulo_numero}', $moduloNumero, $documentXml);
                    $documentXml = str_replace('modulo_numero', $moduloNumero, $documentXml);
                    // Reemplazo robusto para modulo_nombre (soporta espacios y tilde en "módulo")
                    $documentXml = preg_replace('/\{\s*m[oó]dulo(?:[_\s\x{00A0}]*)nombre\s*\}/iu', $moduloNombre, $documentXml);
                    $documentXml = preg_replace('/m[oó]dulo(?:[_\s\x{00A0}]*)nombre/iu', $moduloNombre, $documentXml);
                    // Fallback amplio: cualquier placeholder dentro de {} que contenga modulo...nombre
                    $documentXml = preg_replace('/\{[^}]*m[oó]dulo[^}]*nombre[^}]*\}/iu', $moduloNombre, $documentXml);
                    $documentXml = str_replace('{año}', $añoActual, $documentXml);
                    
                    // Procesar TODOS los estudiantes y crear tabla completa
                    \Log::info('Procesando estudiantes para template:', ['total' => $efsrts->count()]);
                    if (!$efsrts->isEmpty()) {
                        // Buscar la fila de ejemplo en el XML para clonarla (solo filas de datos, no encabezados)
                        // Estrategia: obtener TODAS las <w:tr> y elegir la primera que contenga {#} y
                        // al menos uno de los placeholders de datos (dni, apellidos, empresa, etc.)
                        $filaOriginal = null;
                        if (preg_match_all('/<w:tr[^>]*>.*?<\/w:tr>/s', $documentXml, $todasFilas)) {
                            $placeholdersDatos = [
                                '{estudiante_dni}', 'estudiante_dni',
                                '{estudiante_apellidos}', 'estudiante_apellidos',
                                '{estudiante_nombres}', 'estudiante_nombres',
                                '{empresa_nombre}', 'empresa_nombre',
                                '{docente_nombre}', 'docente_nombre',
                                '{nota}', 'nota',
                                '{creditos}', 'creditos',
                                '{hacumu}', 'hacumu'
                            ];
                            foreach ($todasFilas[0] as $tr) {
                                if (strpos($tr, '{#}') !== false) {
                                    foreach ($placeholdersDatos as $ph) {
                                        if (strpos($tr, $ph) !== false) {
                                            $filaOriginal = $tr;
                                            break 2; // encontrada fila de datos válida
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (!empty($filaOriginal)) {
                            $filasNuevas = '';
                            
                            // Crear una fila para cada estudiante
                            foreach ($efsrts as $index => $efsrt) {
                                $dni = $efsrt->estudiante->persona->dni ?? 'N/A';
                                $apellidos = $efsrt->estudiante->persona->apellidos ?? '';
                                $nombres = $efsrt->estudiante->persona->nombres ?? '';
                                $nombreCompleto = trim($apellidos . ' ' . $nombres);
                                $empresaNombre = strip_tags($efsrt->empresa->razon_social ?? 'N/A');
                                $docenteNombre = trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? ''));
                                $nota = $efsrt->anexo05->total_puntaje ?? '';
                                $creditos = $efsrt->modulo->creditos ?? '';
                                $horasAcum = $efsrt->anexo05->total_horas ?? '';
                                
                                // Crear nueva fila reemplazando variables
                                $nuevaFila = $filaOriginal;
                                $nuevaFila = str_replace('{#}', ($index + 1), $nuevaFila);
                                $nuevaFila = str_replace('{estudiante_dni}', $dni, $nuevaFila);
                                $nuevaFila = str_replace('estudiante_dni', $dni, $nuevaFila);
                                $nuevaFila = str_replace('{estudiante_apellidos}', $nombreCompleto, $nuevaFila);
                                $nuevaFila = str_replace('estudiante_apellidos', $nombreCompleto, $nuevaFila);
                                $nuevaFila = str_replace('{estudiante_nombres}', '', $nuevaFila);
                                $nuevaFila = str_replace('estudiante_nombres', '', $nuevaFila);
                                $nuevaFila = str_replace('{empresa_nombre}', $empresaNombre, $nuevaFila);
                                $nuevaFila = str_replace('empresa_nombre', $empresaNombre, $nuevaFila);
                                $nuevaFila = str_replace('{docente_nombre}', $docenteNombre, $nuevaFila);
                                $nuevaFila = str_replace('docente_nombre', $docenteNombre, $nuevaFila);
                                $nuevaFila = str_replace('{nota}', $nota, $nuevaFila);
                                $nuevaFila = str_replace('{creditos}', $creditos, $nuevaFila);
                                $nuevaFila = str_replace('creditos', $creditos, $nuevaFila);
                                $nuevaFila = str_replace('{hacumu}', $horasAcum, $nuevaFila);
                                $nuevaFila = str_replace('hacumu', $horasAcum, $nuevaFila);

                                // Eliminar llaves sueltas que queden alrededor de los valores en la fila
                                $nuevaFila = str_replace(['{', '}'], '', $nuevaFila);
                                
                                $filasNuevas .= $nuevaFila;
                            }
                            
                            // Reemplazar la fila original con todas las filas nuevas
                            $documentXml = str_replace($filaOriginal, $filasNuevas, $documentXml);
                        } else {
                            // Fallback: reemplazar solo la primera fila si no se puede clonar
                            $primerEfsrt = $efsrts->first();
                            $dni = $primerEfsrt->estudiante->persona->dni ?? 'N/A';
                            $apellidos = $primerEfsrt->estudiante->persona->apellidos ?? '';
                            $nombres = $primerEfsrt->estudiante->persona->nombres ?? '';
                            $nombreCompleto = trim($apellidos . ' ' . $nombres);
                            $empresaNombre = strip_tags($primerEfsrt->empresa->razon_social ?? 'N/A');
                            $docenteNombre = trim(($primerEfsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($primerEfsrt->docenteAsesor->persona->apellidos ?? ''));
                            $nota = $primerEfsrt->anexo05->total_puntaje ?? '';
                            $creditos = $primerEfsrt->modulo->creditos ?? '';
                            $horasAcum = $primerEfsrt->anexo05->total_horas ?? '';
                            
                            $documentXml = str_replace('{#}', '1', $documentXml);
                            $documentXml = str_replace('{estudiante_dni}', $dni, $documentXml);
                            $documentXml = str_replace('estudiante_dni', $dni, $documentXml);
                            $documentXml = str_replace('{estudiante_apellidos}', $nombreCompleto, $documentXml);
                            $documentXml = str_replace('estudiante_apellidos', $nombreCompleto, $documentXml);
                            $documentXml = str_replace('{estudiante_nombres}', '', $documentXml);
                            $documentXml = str_replace('estudiante_nombres', '', $documentXml);
                            $documentXml = str_replace('{empresa_nombre}', $empresaNombre, $documentXml);
                            $documentXml = str_replace('empresa_nombre', $empresaNombre, $documentXml);
                            $documentXml = str_replace('{docente_nombre}', $docenteNombre, $documentXml);
                            $documentXml = str_replace('docente_nombre', $docenteNombre, $documentXml);
                            $documentXml = str_replace('{nota}', $nota, $documentXml);
                            $documentXml = str_replace('{creditos}', $creditos, $documentXml);
                            $documentXml = str_replace('creditos', $creditos, $documentXml);
                            $documentXml = str_replace('{hacumu}', $horasAcum, $documentXml);
                            $documentXml = str_replace('hacumu', $horasAcum, $documentXml);
                        }
                    }
                    
                    // Guardar el XML modificado
                    $zip->addFromString('word/document.xml', $documentXml);
                }

                // Asegurar reemplazo de placeholders en todos los XML de word/ (incluye headers/footers/shapes/footnotes)
                if (method_exists($zip, 'numFiles')) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $stat = $zip->statIndex($i);
                        $entryName = $stat['name'] ?? '';
                        if (preg_match('/^word\/.*\.xml$/i', $entryName)) {
                            $xml = $zip->getFromIndex($i);
                            if ($xml) {
                                // Normalizar runs y NBSP
                                $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
                                $xml = preg_replace('/<\/w:t><\/w:r>\s*<w:r[^>]*><w:t[^>]*>/', '', $xml);
                                $xml = str_replace("\xC2\xA0", ' ', $xml);
                                // Reemplazos encabezado
                                $xml = str_replace('{programa_estudio}', $programaNombre, $xml);
                                $xml = str_replace('programa_estudio', $programaNombre, $xml);
                                $xml = str_replace('{modulo_numero}', $moduloNumero, $xml);
                                $xml = str_replace('modulo_numero', $moduloNumero, $xml);
                                $xml = preg_replace('/\{\s*m[oó]dulo(?:[_\s\x{00A0}]*)nombre\s*\}/iu', $moduloNombre, $xml);
                                $xml = preg_replace('/m[oó]dulo(?:[_\s\x{00A0}]*)nombre/iu', $moduloNombre, $xml);
                                // Fallback amplio
                                $xml = preg_replace('/\{[^}]*m[oó]dulo[^}]*nombre[^}]*\}/iu', $moduloNombre, $xml);
                                $xml = str_replace('{año}', $añoActual, $xml);
                                $zip->addFromString($entryName, $xml);
                            }
                        }
                    }
                }

                $zip->close();
            }
            
            // Generar el archivo final
            $fileName = 'Anexo01_Padron_EFSRT_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }
            
            // Copiar el template limpio como archivo final
            copy($templateLimpio, $tempFile);
            
            \Log::info('Padrón generado usando template limpio: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error usando template limpio: ' . $e->getMessage());
            // Fallback al método desde cero si falla
            return $this->generarPadronConDisenoOriginal($efsrts, $programa, $modulo, $año);
        }
    }

    /**
     * Generar padrón con el diseño original de la plantilla pero desde cero
     */
    private function generarPadronConDisenoOriginal($efsrts, $programa, $modulo, $año)
    {
        try {
            $phpWord = new PhpWord();
            
            // Configurar el documento
            $section = $phpWord->addSection([
                'marginTop' => 800,
                'marginBottom' => 800,
                'marginLeft' => 800,
                'marginRight' => 800,
            ]);

            // Crear encabezado con logos (simulando tu plantilla)
            $headerTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 50]);
            $headerTable->addRow();
            
            // Celda izquierda - Logo IES
            $leftCell = $headerTable->addCell(2000);
            $leftCell->addText('IES', ['bold' => true, 'size' => 16, 'color' => 'blue']);
            $leftCell->addText('INSTITUTO', ['bold' => true, 'size' => 8]);
            
            // Celda central - Información del instituto
            $centerCell = $headerTable->addCell(6000);
            $centerCell->addText('INSTITUTO DE EDUCACION SUPERIOR PÚBLICO "JOSE CARLOS MARIATEGUI"', 
                ['bold' => true, 'size' => 12], ['alignment' => 'center']);
            $centerCell->addText('SAMEGUA - MOQUEGUA', 
                ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $centerCell->addText('Autorización de Funcionamiento R.S. Nº 131-83-ED. Revalidado con R.D. Nº 247-05-ED', 
                ['size' => 8], ['alignment' => 'center']);
            $centerCell->addText('LICENCIADO R.M. Nº 577-2019-MINEDU y R.M. Nº 655-2024-MINEDU', 
                ['bold' => true, 'size' => 8], ['alignment' => 'center']);
            $centerCell->addText('1975-2025. BODAS DE ORO. "50 años formando profesionales técnicos"', 
                ['bold' => true, 'size' => 8], ['alignment' => 'center']);
            $centerCell->addText('"Año de la recuperación y consolidación de la economía peruana"', 
                ['italic' => true, 'size' => 7], ['alignment' => 'center']);
            
            // Celda derecha - Sello LICENCIADO
            $rightCell = $headerTable->addCell(2000);
            $rightCell->addText('LICENCIADO', ['bold' => true, 'size' => 10, 'color' => 'blue'], ['alignment' => 'center']);
            
            $section->addTextBreak(2);
            
            // Título ANEXO 01
            $section->addText('ANEXO 01', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
            $section->addTextBreak(1);
            
            // Título del padrón
            $section->addText('PADRÓN DE CONSOLIDADO DE EXPERIENCIAS FORMATIVAS EN SITUACIONES REALES DE TRABAJO', 
                ['bold' => true, 'size' => 12], ['alignment' => 'center']);
            $section->addTextBreak(2);
            
            // Información del programa y módulo con datos reales
            $programaNombre = $programa->nombre ?? 'N/A';
            $moduloNumero = $modulo->numero ?? 'N/A';
            $moduloNombre = $modulo->nombre ?? 'N/A';
            $añoActual = $año ?? date('Y');
            
            $section->addText("PROGRAMA DE ESTUDIOS: {$programaNombre}", ['bold' => true, 'size' => 11]);
            $section->addTextBreak(1);
            $section->addText("MÓDULO {$moduloNumero}: {$moduloNombre}     AÑO: {$añoActual}", ['bold' => true, 'size' => 11]);
            $section->addTextBreak(2);
            
            // Crear tabla de estudiantes
            $table = $section->addTable([
                'borderSize' => 6, 
                'borderColor' => '000000', 
                'cellMargin' => 50
            ]);
            
            // Encabezados de la tabla
            $table->addRow();
            $table->addCell(800)->addText("N°", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(1500)->addText("CODIGO\n(DNI)", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(3000)->addText("APELLIDOS Y\nNOMBRES", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(2500)->addText("EMPRESA O\nINSTITUCIÓN", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(2000)->addText("DOCENTE\nSUPERVISOR", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(800)->addText("NOTA", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(800)->addText("CRED.", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            $table->addCell(1000)->addText("HORAS\nACUM.", ['bold' => true, 'size' => 10], ['alignment' => 'center']);
            
            // Datos de estudiantes
            $contador = 1;
            foreach ($efsrts as $efsrt) {
                $table->addRow();
                
                $dni = $efsrt->estudiante->persona->dni ?? 'N/A';
                $apellidos = $efsrt->estudiante->persona->apellidos ?? '';
                $nombres = $efsrt->estudiante->persona->nombres ?? '';
                $nombreCompleto = trim($apellidos . ' ' . $nombres);
                $empresaNombre = strip_tags($efsrt->empresa->razon_social ?? 'N/A');
                $docenteNombre = trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? ''));
                $nota = $efsrt->anexo05->total_puntaje ?? '';
                $creditos = $efsrt->modulo->creditos ?? '';
                $horasAcum = $efsrt->anexo05->total_horas ?? '';
                
                $table->addCell(800)->addText($contador++, ['size' => 10], ['alignment' => 'center']);
                $table->addCell(1500)->addText($dni, ['size' => 10], ['alignment' => 'center']);
                $table->addCell(3000)->addText($nombreCompleto, ['size' => 10]);
                $table->addCell(2500)->addText($empresaNombre, ['size' => 9]);
                $table->addCell(2000)->addText($docenteNombre, ['size' => 9]);
                $table->addCell(800)->addText($nota, ['size' => 10], ['alignment' => 'center']);
                $table->addCell(800)->addText($creditos, ['size' => 10], ['alignment' => 'center']);
                $table->addCell(1000)->addText($horasAcum, ['size' => 10], ['alignment' => 'center']);
            }
            
            $section->addTextBreak(3);
            
            // Firmas
            $firmasTable = $section->addTable(['borderSize' => 0]);
            $firmasTable->addRow();
            $firmasTable->addCell(4000)->addText('_________________________________', [], ['alignment' => 'center']);
            $firmasTable->addCell(1000)->addText('', []);
            $firmasTable->addCell(4000)->addText('_________________________________', [], ['alignment' => 'center']);
            
            $firmasTable->addRow();
            $firmasTable->addCell(4000)->addText('Coordinador de área académica', ['size' => 10], ['alignment' => 'center']);
            $firmasTable->addCell(1000)->addText('', []);
            $firmasTable->addCell(4000)->addText('Docente Supervisor', ['size' => 10], ['alignment' => 'center']);
            
            // Generar el archivo
            $fileName = 'Anexo01_Padron_EFSRT_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            
            \Log::info('Padrón generado desde cero con diseño original: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error generando padrón con diseño original: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el padrón: ' . $e->getMessage());
        }
    }

    /**
     * Diagnosticar la plantilla para ver qué variables contiene
     */
    private function diagnosticarPlantilla($templatePath)
    {
        try {
            // Leer el contenido del archivo Word como ZIP
            $zip = new \ZipArchive();
            if ($zip->open($templatePath) === TRUE) {
                // Leer el documento principal
                $documentXml = $zip->getFromName('word/document.xml');
                if ($documentXml) {
                    // Buscar variables en formato {variable}
                    preg_match_all('/\{([^}]+)\}/', $documentXml, $matches);
                    if (!empty($matches[1])) {
                        \Log::info('Variables encontradas en la plantilla:', $matches[1]);
                    } else {
                        \Log::warning('No se encontraron variables en formato {variable} en la plantilla');
                    }
                }
                $zip->close();
            }
        } catch (\Exception $e) {
            \Log::error('Error al diagnosticar plantilla: ' . $e->getMessage());
        }
    }

    /**
     * Generar padrón EFSRT desde cero (sin plantilla)
     */
    private function generarPadronDesdeCero($efsrts, $programa, $modulo, $año)
    {
        try {
            $phpWord = new PhpWord();
            
            // Configurar el documento con encabezados y pies de página
            $section = $phpWord->addSection([
                'marginTop' => 1200,
                'marginBottom' => 1200,
                'marginLeft' => 800,
                'marginRight' => 800,
                'headerHeight' => 600,
                'footerHeight' => 400
            ]);

            // Agregar encabezado
            $header = $section->addHeader();
            
            // Encabezado completo centrado (tamaños más pequeños)
            $header->addText('INSTITUTO DE EDUCACION SUPERIOR PÚBLICO "JOSE CARLOS MARIATEGUI"', 
                ['bold' => true, 'size' => 10, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('SAMEGUA - MOQUEGUA', 
                ['bold' => true, 'size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('Autorización de Funcionamiento R.S. Nº 131-83-ED. Revalidado con R.D. Nº 247-05-ED', 
                ['size' => 7, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('LICENCIADO R.M. N° 577-2019-MINEDU y R.M. N° 655-2024-MINEDU', 
                ['bold' => true, 'size' => 7, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('1975-2025. BODAS DE ORO. "50 años formando profesionales técnicos"', 
                ['bold' => true, 'size' => 7, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $header->addText('"Año de la recuperación y consolidación de la economía peruana"', 
                ['italic' => true, 'size' => 6, 'name' => 'Arial'], 
                ['alignment' => 'center']);

            // Agregar pie de página
            $footer = $section->addFooter();
            
            // Pie de página simple centrado
            $footer->addText('Av. Ejército 502 - Samegua - Moquegua | Teléfono: (053) 463-078', 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $footer->addPreserveText('Página {PAGE} - Generado el ' . date('d/m/Y'), 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);

            // Estilos
            $titleStyle = ['bold' => true, 'size' => 14, 'name' => 'Arial'];
            $headerStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
            $normalStyle = ['size' => 10, 'name' => 'Arial'];
            $centerAlign = ['alignment' => 'center'];
            
            // Ya no duplicar el encabezado en el cuerpo (ya está en el header)
            $section->addTextBreak(1);
            
            // Título del anexo
            $section->addText('ANEXO 01', $titleStyle, $centerAlign);
            $section->addText('PADRÓN DE CONSOLIDADO DE EXPERIENCIAS FORMATIVAS EN SITUACIONES REALES DE TRABAJO', $headerStyle, $centerAlign);
            
            $section->addTextBreak(1);
            
            // Información del programa
            $section->addText('PROGRAMA DE ESTUDIOS: ' . ($programa->nombre ?? 'N/A'), $normalStyle);
            $section->addText('MÓDULO ' . ($modulo->numero ?? 'N/A') . ': ' . ($modulo->nombre ?? 'N/A') . '     AÑO: ' . ($año ?? date('Y')), $normalStyle);
            
            $section->addTextBreak(1);
            
            // Crear tabla
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 50,
                'width' => 100 * 50 // 100% width
            ]);
            
            // Encabezados de la tabla
            $table->addRow();
            $table->addCell(800)->addText('N°', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(1500)->addText('CODIGO (DNI)', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(3000)->addText('APELLIDOS Y NOMBRES', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(2500)->addText('EMPRESA O INSTITUCIÓN', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(2000)->addText('DOCENTE SUPERVISOR', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(800)->addText('NOTA', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(800)->addText('CRED.', ['bold' => true, 'size' => 9], $centerAlign);
            $table->addCell(1000)->addText('HORAS ACUM.', ['bold' => true, 'size' => 9], $centerAlign);
            
            // Agregar filas de estudiantes
            $contador = 1;
            foreach ($efsrts as $efsrt) {
                $table->addRow();
                
                // Datos del estudiante
                $dni = $efsrt->estudiante->persona->dni ?? 'N/A';
                $apellidos = $efsrt->estudiante->persona->apellidos ?? '';
                $nombres = $efsrt->estudiante->persona->nombres ?? '';
                $nombreCompleto = trim($apellidos . ' ' . $nombres);
                $empresaNombre = strip_tags($efsrt->empresa->razon_social ?? 'N/A');
                $docenteNombre = trim(($efsrt->docenteAsesor->persona->nombres ?? '') . ' ' . ($efsrt->docenteAsesor->persona->apellidos ?? ''));
                
                // Obtener datos del Anexo 05
                $nota = $efsrt->anexo05->total_puntaje ?? '';
                $creditos = $efsrt->modulo->creditos ?? '';
                $horasAcum = $efsrt->anexo05->total_horas ?? '';
                
                $table->addCell(800)->addText($contador++, ['size' => 9], $centerAlign);
                $table->addCell(1500)->addText($dni, ['size' => 9], $centerAlign);
                $table->addCell(3000)->addText($nombreCompleto, ['size' => 9]);
                $table->addCell(2500)->addText($empresaNombre, ['size' => 9]);
                $table->addCell(2000)->addText($docenteNombre, ['size' => 9]);
                $table->addCell(800)->addText($nota, ['size' => 9], $centerAlign);
                $table->addCell(800)->addText($creditos, ['size' => 9], $centerAlign);
                $table->addCell(1000)->addText($horasAcum, ['size' => 9], $centerAlign);
            }
            
            // Generar el archivo
            $fileName = 'Anexo01_Padron_EFSRT_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            
            \Log::info('Documento generado desde cero exitosamente: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error generando documento desde cero: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el documento: ' . $e->getMessage());
        }
    }

    /**
     * Generar constancia EFSRT desde cero (nuevo método)
     */
    public function generarConstancia(Request $request)
    {
        try {
            $estudianteId = $request->input('estudiante_id');
            $tipo = $request->input('tipo', 'efsrt');
            $año = $request->input('año', date('Y'));

            // Obtener TODOS los EFSRTs del estudiante (como el padrón)
            $efsrts = EFSRT::with([
                'estudiante.persona',
                'estudiante.programaEstudio',
                'modulo',
                'docenteAsesor.persona',
                'empresa',
                'semestre',
                'anexo05'
            ])->where('id_estudiante', $estudianteId)->get();

            if ($efsrts->isEmpty()) {
                $estudiante = \App\Models\Estudiante::with('persona', 'programaEstudio')->find($estudianteId);
                
                if (!$estudiante) {
                    return back()->with('error', 'El estudiante seleccionado no existe en el sistema.');
                }
                
                $mensaje = "No se puede generar la constancia para el estudiante seleccionado:\n\n";
                $mensaje .= "• Estudiante: " . ($estudiante->persona->nombres ?? '') . " " . ($estudiante->persona->apellidos ?? '') . "\n";
                $mensaje .= "• DNI: " . ($estudiante->persona->dni ?? 'N/A') . "\n";
                $mensaje .= "• Programa: " . ($estudiante->programaEstudio->nombre ?? 'N/A') . "\n\n";
                $mensaje .= "Motivo: No tiene registros de EFSRT (Experiencias Formativas en Situaciones Reales de Trabajo)\n\n";
                $mensaje .= "Para generar la constancia, el estudiante debe tener:\n";
                $mensaje .= "- EFSRT registrado y completo\n";
                $mensaje .= "- Empresa asignada\n";
                $mensaje .= "- Docente asesor asignado\n";
                $mensaje .= "- Módulo y Semestre definidos\n\n";
                $mensaje .= "Verifica que el estudiante tenga su EFSRT completo en el sistema.";
                
                return back()->with('error', $mensaje);
            }

            // Intentar usar la plantilla oficial constancia.docx
            $templatePath = storage_path('app/templates/constancia.docx');
            if (file_exists($templatePath)) {
                \Log::info('Generando constancia usando plantilla oficial constancia.docx');
                return $this->generarConstanciaUsandoTemplateOficial($efsrts, $año, $templatePath);
            }

            // Fallback si no existe la plantilla
            return $this->generarConstanciaDesdeCero($efsrts, $año);

        } catch (\Exception $e) {
            \Log::error('Error generando constancia: ' . $e->getMessage());
            return back()->with('error', 'Error al generar la constancia: ' . $e->getMessage());
        }
    }

    /**
     * Generar constancia EFSRT desde cero (sin plantilla)
     */
    private function generarConstanciaDesdeCero($efsrts, $año)
    {
        try {
            $phpWord = new PhpWord();
            
            // Configurar el documento solo con pie de página (sin encabezado)
            $section = $phpWord->addSection([
                'marginTop' => 800,
                'marginBottom' => 1400,
                'marginLeft' => 1200,
                'marginRight' => 1200,
                'footerHeight' => 400
            ]);

            // Las constancias NO tienen encabezado, solo pie de página

            // Agregar pie de página
            $footer = $section->addFooter();
            
            // Pie de página simple para constancias
            $footer->addText('Av. Ejército 502 - Samegua - Moquegua | www.iestpjcm.edu.pe | Teléfono: (053) 463-078', 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);
            $footer->addPreserveText('Documento generado el ' . date('d/m/Y') . ' - Página {PAGE}', 
                ['size' => 8, 'name' => 'Arial'], 
                ['alignment' => 'center']);

            // Estilos
            $titleStyle = ['bold' => true, 'size' => 14, 'name' => 'Arial'];
            $headerStyle = ['bold' => true, 'size' => 12, 'name' => 'Arial'];
            $normalStyle = ['size' => 11, 'name' => 'Arial'];
            $centerAlign = ['alignment' => 'center'];
            $justifyAlign = ['alignment' => 'both'];
            
            // Ya no duplicar el encabezado en el cuerpo (ya está en el header)
            $section->addTextBreak(1);
            
            // Título de la constancia
            $section->addText('CONSTANCIA DE REALIZACIÓN Y APROBACIÓN DE LAS', $headerStyle, $centerAlign);
            $section->addText('EXPERIENCIAS FORMATIVAS EN SITUACIONES REALES DE TRABAJO', $headerStyle, $centerAlign);
            
            $section->addTextBreak(2);
            
            // Tomar el primer EFSRT para datos básicos del estudiante
            $efsrt = $efsrts->first();
            
            // Datos del estudiante
            $apellidos = $efsrt->estudiante->persona->apellidos ?? '';
            $nombres = $efsrt->estudiante->persona->nombres ?? '';
            $dni = $efsrt->estudiante->persona->dni ?? 'N/A';
            $programa = $efsrt->estudiante->programaEstudio->nombre ?? 'N/A';
            
            // Calcular totales de todos los EFSRTs (como el padrón)
            $totalHoras = 0;
            $totalCreditos = 0;
            $totalNotas = 0;
            $contadorEfsrts = 0;
            $modulosNombres = [];
            
            foreach ($efsrts as $efsrtItem) {
                // Recopilar nombres de módulos
                if ($efsrtItem->modulo && $efsrtItem->modulo->nombre) {
                    $modulosNombres[] = $efsrtItem->modulo->nombre;
                }
                
                // Sumar horas del anexo05
                if ($efsrtItem->anexo05) {
                    $horas = $efsrtItem->anexo05->total_horas ?? 0;
                    $totalHoras += $horas;
                }
                
                // Sumar créditos del módulo
                if ($efsrtItem->modulo) {
                    $creditos = $efsrtItem->modulo->creditos ?? 0;
                    $totalCreditos += $creditos;
                }
                
                // Sumar notas del anexo05
                if ($efsrtItem->anexo05) {
                    $nota = $efsrtItem->anexo05->total_puntaje ?? 0;
                    if ($nota > 0) {
                        $totalNotas += $nota;
                        $contadorEfsrts++;
                    }
                }
            }
            
            // Calcular promedio de notas
            $promedioNotas = $contadorEfsrts > 0 ? round($totalNotas / $contadorEfsrts, 2) : 0;
            $modulosTexto = !empty($modulosNombres) ? implode(', ', array_unique($modulosNombres)) : 'N/A';
            
            // Cuerpo de la constancia
            $texto1 = "LA COORDINADORA DEL PROGRAMA DE ESTUDIOS DEL ÁREA ACADÉMICA DE ARQUITECTURA DE PLATAFORMAS Y SERVICIOS DE TECNOLOGÍAS DE LA INFORMACIÓN, DEL INSTITUTO DE EDUCACIÓN SUPERIOR PÚBLICO \"JOSÉ CARLOS MARIÁTEGUI\". QUE SUSCRIBE:";
            $section->addText($texto1, $normalStyle, $justifyAlign);
            
            $section->addTextBreak(1);
            $section->addText('HACE CONSTAR:', ['bold' => true, 'size' => 11, 'name' => 'Arial'], $centerAlign);
            $section->addTextBreak(1);
            
            $texto2 = "Que, Sr(a): {$apellidos} {$nombres}, ex alumno(a) del Programa de Estudios de {$programa}, de este Instituto, identificado(a) con DNI N° {$dni} y código de matrícula N° {$dni}; ha realizado sus Experiencias Formativas en Situaciones Reales de Trabajo - EFSRT, conforme al siguiente detalle:";
            $section->addText($texto2, $normalStyle, $justifyAlign);
            
            $section->addTextBreak(2);
            
            // Tabla de módulos
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 100
            ]);
            
            // Encabezados
            $table->addRow();
            $table->addCell(3000)->addText('MÓDULOS FORMATIVOS', ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(2500)->addText('LUGAR', ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1200)->addText('HORAS', ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1200)->addText('CRÉDITOS', ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1000)->addText('NOTA', ['bold' => true, 'size' => 10], $centerAlign);
            
            // Agregar filas de TODOS los módulos (como el padrón)
            $contador = 1;
            foreach ($efsrts as $efsrtItem) {
                $table->addRow();
                
                // Datos del módulo
                $moduloNombre = $efsrtItem->modulo->nombre ?? 'N/A';
                $empresaNombre = $efsrtItem->empresa->razon_social ?? 'N/A';
                $horasModulo = $efsrtItem->anexo05->total_horas ?? '0';
                $creditosModulo = $efsrtItem->modulo->creditos ?? '0';
                $notaModulo = $efsrtItem->anexo05->total_puntaje ?? '0';
                
                $table->addCell(3000)->addText("{$contador}. {$moduloNombre}", ['size' => 10]);
                $table->addCell(2500)->addText($empresaNombre, ['size' => 10]);
                $table->addCell(1200)->addText($horasModulo, ['size' => 10], $centerAlign);
                $table->addCell(1200)->addText($creditosModulo, ['size' => 10], $centerAlign);
                $table->addCell(1000)->addText($notaModulo, ['size' => 10], $centerAlign);
                
                $contador++;
            }
            
            // Fila de totales
            $table->addRow();
            $table->addCell(3000)->addText('TOTALES:', ['bold' => true, 'size' => 10]);
            $table->addCell(2500)->addText('', ['size' => 10]);
            $table->addCell(1200)->addText($totalHoras, ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1200)->addText($totalCreditos, ['bold' => true, 'size' => 10], $centerAlign);
            $table->addCell(1000)->addText($promedioNotas, ['bold' => true, 'size' => 10], $centerAlign);
            
            $section->addTextBreak(2);
            
            // Texto final
            $texto3 = "Así, consta en los archivos de este del Programa de Estudios y en el Área de secretaría Académica.";
            $section->addText($texto3, $normalStyle, $justifyAlign);
            
            $section->addTextBreak(1);
            
            $texto4 = "Se otorga la presente constancia, a solicitud escrita del interesado(a), con FUT N° 6313-2025-IES.JCM, para los fines de cumplimiento del proceso de titulación, de conformidad a la Resolución Viceministerial N° 049-2022-MINEDU.";
            $section->addText($texto4, $normalStyle, $justifyAlign);
            
            $section->addTextBreak(3);
            
            // Fecha y lugar
            $fechaTexto = "Samegua, {$año} marzo " . date('d');
            $section->addText($fechaTexto, $normalStyle, $centerAlign);
            
            // Generar el archivo
            $fileName = 'Constancia_EFSRT_' . $dni . '_' . date('Ymd') . '.docx';
            $tempFile = storage_path('app/temp/' . $fileName);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0777, true);
            }
            
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            
            \Log::info('Constancia generada exitosamente: ' . $fileName);
            
            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error generando constancia desde cero: ' . $e->getMessage());
            return back()->with('error', 'Error al generar la constancia: ' . $e->getMessage());
        }
    }
}
