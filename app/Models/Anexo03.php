<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anexo03 extends Model
{
    use HasFactory;

    protected $table = 'anexos_03';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_programa_estudio',
        'id_modulo',
        'nro_modulo',
        'fecha_desde',
        'fecha_hasta',
        'horario',
        'observaciones',
        'pago_por',
        'movilidad',
        'otros',
        'solo_EFSRT',
        'idEmpresa',
        'detalle_otros',
        'id_EFSRT'
    ];

    /**
     * Relación: Un Anexo03 pertenece a una Empresa (opcional).
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idEmpresa');
    }

    /**
     * Relación: Un Anexo03 pertenece a una EFSRT (opcional).
     */
    public function efsrt()
    {
        return $this->belongsTo(EFSRT::class, 'id_EFSRT');
    }

    /**
     * Relación: Un Anexo03 pertenece a un Programa de Estudios.
     */
    public function programaEstudio()
    {
        return $this->belongsTo(ProgramaEstudio::class, 'id_programa_estudio');
    }

    /**
     * Relación: Un Anexo03 pertenece a un Módulo.
     */
    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'id_modulo');
    }
}