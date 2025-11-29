<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramaEstudio extends Model
{
    use HasFactory;

    protected $table = 'programas_estudios';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'logo',
        'idTurno',
        'codigo'
    ];

    /**
     * Relación: Un ProgramaEstudio pertenece a un Turno.
     */
    public function turno()
    {
        return $this->belongsTo(Turno::class, 'idTurno');
    }

    /**
     * Relación: Un ProgramaEstudio puede tener muchos PlanesEstudio.
     */
    public function planesEstudio()
    {
        return $this->hasMany(PlanEstudio::class, 'id_programa');
    }

    /**
     * Relación: Un ProgramaEstudio puede tener muchos Docentes.
     */
    public function docentes()
    {
        return $this->hasMany(Docente::class, 'id_programa');
    }

    /**
     * Relación: Un ProgramaEstudio puede tener muchos Estudiantes.
     */
    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'id_programa');
    }
}