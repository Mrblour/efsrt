<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    // Nombre de la tabla si no sigue la convención de nombres plurales de Laravel
    protected $table = 'tipos_documentos';

    // Clave primaria si no es 'id'
    protected $primaryKey = 'id';

    // Desactivar timestamps si no usas 'created_at' y 'updated_at'
    public $timestamps = false;

    // Campos que pueden ser asignados masivamente
    protected $fillable = ['tipo'];

    /**
     * Relación: Un TipoDocumento puede tener muchas Personas.
     */
    public function personas()
    {
        return $this->hasMany(Persona::class, 'id_tipo_documento');
    }
}