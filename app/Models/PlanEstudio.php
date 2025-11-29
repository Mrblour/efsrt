<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEstudio extends Model
{
    use HasFactory;

    protected $table = 'planes_estudio';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'anio',
        'activo',
        'id_programa',
        'descripcion'
    ];

    /**
     * Relación: Un PlanEstudio pertenece a un ProgramaEstudio.
     */
    public function programaEstudio()
    {
        return $this->belongsTo(ProgramaEstudio::class, 'id_programa');
    }

    /**
     * Relación: Un PlanEstudio puede tener muchos Módulos.
     */
    public function modulos()
    {
        return $this->hasMany(Modulo::class, 'id_plan');
    }
}