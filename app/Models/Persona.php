<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';
    protected $primaryKey = 'id';
    public $timestamps = false; // Ajusta según si usas created_at y updated_at

    protected $fillable = [
        'nombres',
        'apellidos',
        'dni',
        'correo',
        'direccion',
        'telefono',
        'password', // ¡Importante: Hashear contraseñas antes de guardar!
        'usuario',
        'fecha_nacimiento',
        'id_tipo_documento'
    ];

    // Ocultar el password al serializar el modelo
    protected $hidden = [
        'password',
    ];

    /**
     * Relación: Una Persona pertenece a un TipoDocumento.
     */
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }

    /**
     * Relación: Una Persona puede ser un Estudiante (uno a uno).
     */
    public function estudiante()
    {
        return $this->hasOne(Estudiante::class, 'id'); // 'id' es la clave foránea en estudiantes que referencia personas.id
    }

    /**
     * Relación: Una Persona puede ser un Docente (uno a uno).
     */
    public function docente()
    {
        return $this->hasOne(Docente::class, 'id'); // 'id' es la clave foránea en docentes que referencia personas.id
    }

    /**
     * Relación: Una Persona puede ser un Representante (uno a uno).
     */
    public function representante()
    {
        return $this->hasOne(Representante::class, 'id'); // 'id' es la clave foránea en representantes que referencia personas.id
    }

    /**
     * Relación: Una Persona puede tener un User asociado (uno a uno).
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id_persona');
    }
}