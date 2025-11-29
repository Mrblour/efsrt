<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semestre extends Model
{
    use HasFactory;

    protected $table = 'semestres';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['semestre'];

    /**
     * Relación: Un Semestre puede estar en muchas EFSRT.
     */
    public function efsrts()
    {
        return $this->hasMany(EFSRT::class, 'id_semestre');
    }
}