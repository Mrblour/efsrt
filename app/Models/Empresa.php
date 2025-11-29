<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'razon_social',
        'direccion',
        'telefono',
        'rubro',
        'id_representante',
        'RUC',
        'es_activa'
    ];

    /**
     * Relación: Una Empresa pertenece a un Representante.
     */
    public function representante()
    {
        return $this->belongsTo(Representante::class, 'id_representante');
    }

    /**
     * Relación: Una Empresa puede tener muchas EFSRT.
     */
    public function efsrts()
    {
        return $this->hasMany(EFSRT::class, 'id_empresa');
    }

    /**
     * Relación: Una Empresa puede estar en Anexo03.
     */
    public function anexos03()
    {
        return $this->hasMany(Anexo03::class, 'idEmpresa');
    }

    /**
     * Relación: Una Empresa puede estar en Anexo04.
     */
    public function anexos04()
    {
        return $this->hasMany(Anexo04::class, 'id_empresa');
    }

    /**
     * Relación: Una Empresa puede estar en Anexo05.
     */
    public function anexos05()
    {
        return $this->hasMany(Anexo05::class, 'idEmpresa');
    }
}