<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EFSRT;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class DocumentoController extends Controller
{
    public function generarWord($codigo_tramite)
    {
        $efsrt = EFSRT::with(['estudiante.persona', 'empresa.representante.persona'])
            ->where('codigo_tramite', $codigo_tramite)
            ->first();

        if (!$efsrt) {
            return "No se encontró la EFSRT para el código: $codigo_tramite";
        }

        $phpWord = new PhpWord();

        // Configurar márgenes
        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        $estudiante = $efsrt->estudiante->persona;
        $empresa = $efsrt->empresa;

        // Encabezado con instituto
        $header = $section->addHeader();
        $header->addText("INSTITUTO SUPERIOR JOSÉ CARLOS MARIÁTEGUI", ['bold' => true, 'size' => 14], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $header->addText("FACULTAD: ARQUITECTURA DE PLATAFORMAS Y SERVICIOS DE TECNOLOGÍAS DE INFORMACIÓN", ['size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $header->addTextBreak(1);

        // Título
        $section->addText("CONSTANCIA DE PRÁCTICAS", ['bold' => true, 'size' => 16], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTextBreak(1);

        // Texto introductorio
        $section->addText(
            "La Oficina de Seguimiento al Estudiante o Egresado y Prácticas Preprofesionales certifica que el/la estudiante:",
            [], 
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]
        );
        $section->addTextBreak(1);

        // Nombre del estudiante
        $section->addText(
            "{$estudiante->nombres} {$estudiante->apellidos}",
            ['bold' => true, 'size' => 14],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Detalles de la constancia
        $nombreEscuela = $efsrt->estudiante->nombre_escuela ?? '[Nombre de la Escuela]';
        $totalHoras = $efsrt->total_horas ?? '[Total Horas]';
        $fechaInicio = \Carbon\Carbon::parse($efsrt->fecha_inicio)->format('d/m/Y');
        $fechaFin = \Carbon\Carbon::parse($efsrt->fecha_fin)->format('d/m/Y');

        $section->addText(
            "Con código de matrícula N° {$efsrt->estudiante->cui} del Instituto Superior José Carlos Mariátegui, egresado de la escuela {$nombreEscuela}, ha completado exitosamente el programa de prácticas preprofesionales en la empresa {$empresa->razon_social}, durante el periodo comprendido entre el {$fechaInicio} y el {$fechaFin}, acumulando un total de {$totalHoras} horas.",
            [],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH]
        );
        $section->addTextBreak(2);

        // Fecha de expedición
        $fechaHoy = now();
        $section->addText(
            "Expedido en la ciudad de Arequipa, a los {$fechaHoy->day} días del mes de {$fechaHoy->locale('es')->monthName} del {$fechaHoy->year}.",
            [],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]
        );
        $section->addTextBreak(2);

        // Firmas
        $table = $section->addTable(['alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER, 'cellMargin' => 100]);
        $table->addRow();
        $table->addCell(5000)->addText(
            "_________________________\nDirector(a) del Instituto Superior José Carlos Mariátegui", 
            ['size' => 12], 
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        $table->addCell(5000)->addText(
            "_________________________\nDecano(a) de la Facultad", 
            ['size' => 12], 
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );

        // Guardar archivo
        $fileName = 'constancia_' . $efsrt->codigo_tramite . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
} 
