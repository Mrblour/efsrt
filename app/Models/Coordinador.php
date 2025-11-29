<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinador extends Model
{
    protected $table = 'coordinadors';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'id_programa',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id');
    }

    public function programaEstudio()
    {
        return $this->belongsTo(ProgramaEstudio::class, 'id_programa');
    }
}
