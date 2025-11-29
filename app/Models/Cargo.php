<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $table = 'cargos';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['nombre'];

    /**
     * Relación: Un Cargo puede tener muchos Representantes.
     */
    public function representantes()
    {
        return $this->hasMany(Representante::class, 'id_cargo');
    }
}