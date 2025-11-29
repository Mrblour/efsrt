<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'horas',
        'creditos',
        'numero',
        'id_plan'
    ];

    /**
     * Relación: Un Módulo pertenece a un PlanEstudio.
     */
    public function planEstudio()
    {
        return $this->belongsTo(PlanEstudio::class, 'id_plan');
    }

    /**
     * Relación: Un Módulo puede estar en muchas EFSRT.
     */
    public function efsrts()
    {
        return $this->hasMany(EFSRT::class, 'id_modulo');
    }
}
