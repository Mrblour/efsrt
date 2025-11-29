<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Representante extends Model
{
    use HasFactory;

    protected $table = 'representantes';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id', // ID del representante (que es el mismo que el ID de la persona)
        'id_cargo',
        'cargo', // Aunque sea redundante, si lo usas en el fillable, Laravel lo permitirá
        'es_firmante'
    ];

    /**
     * Relación: Un Representante pertenece a una Persona.
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id');
    }

    /**
     * Relación: Un Representante pertenece a un Cargo.
     */
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    /**
     * Relación: Un Representante puede ser el representante de muchas Empresas.
     */
    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'id_representante');
    }
}
