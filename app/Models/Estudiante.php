<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';
    protected $primaryKey = 'id';
    public $incrementing = false; // IMPORTANTE: desactiva autoincremental
    public $timestamps = false;

    protected $fillable = [
        'id', // IMPORTANTE: permitir asignación del ID manual
        'id_programa'
    ];

    public function programaEstudio()
    {
        return $this->belongsTo(ProgramaEstudio::class, 'id_programa');
    }

    public function efsrts()
    {
        return $this->hasMany(EFSRT::class, 'id_estudiante');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id');
    }

}
