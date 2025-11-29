<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    protected $table = 'turnos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['turno'];

    /**
     * Relación: Un Turno puede tener muchos ProgramasEstudio.
     */
    public function programasEstudio()
    {
        return $this->hasMany(ProgramaEstudio::class, 'idTurno');
    }
}
