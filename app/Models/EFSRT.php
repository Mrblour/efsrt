<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EFSRT extends Model
{
    use HasFactory;

    protected $table = 'efsrt'; // Nombre de la tabla (singular)
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_estudiante',
        'fecha_registro',
        'id_modulo',
        'fecha_inicio',
        'fecha_fin',
        'id_docente_asesor',
        'id_empresa',
        'id_semestre',
        'anexo3_PDF',
        'anexo4_PDF',
        'anexo5_PDF',
        'fecha_anexo3',
        'fecha_anexo4',
        'fecha_anexo5',
        'codigo_tramite'
    ];

    /**
     * Relación: Una EFSRT pertenece a un Estudiante.
     */
    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class, 'id_estudiante');
    }

    /**
     * Relación: Una EFSRT pertenece a un Módulo.
     */
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }

    /**
     * Relación: Una EFSRT pertenece a un Docente (asesor).
     */
    public function docenteAsesor()
    {
        return $this->belongsTo(Docente::class, 'id_docente_asesor');
    }

    /**
     * Relación: Una EFSRT pertenece a una Empresa.
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /**
     * Relación: Una EFSRT pertenece a un Semestre.
     */
    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'id_semestre');
    }

    /**
     * Relación: Una EFSRT puede tener un Anexo03.
     */
    public function anexo03()
    {
        return $this->hasOne(Anexo03::class, 'id_EFSRT');
    }

    /**
     * Relación: Una EFSRT puede tener un Anexo04.
     */
    public function anexo04()
    {
        return $this->hasOne(Anexo04::class, 'id_EFSRT');
    }

    /**
     * Relación: Una EFSRT puede tener un Anexo05.
     */
    public function anexo05()
    {
        return $this->hasOne(Anexo05::class, 'id_EFSRT');
    }
}