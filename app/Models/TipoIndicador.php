<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoIndicador extends Model
{
    use HasFactory;

    protected $table = 'tipos_indicadores';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'item',
        'nombre'
    ];

    /**
     * Relación: Un TipoIndicador puede tener muchos IndicadoresEvaluacion.
     */
    public function indicadoresEvaluacion()
    {
        return $this->hasMany(IndicadorEvaluacion::class, 'id_tipo_indicador');
    }
}
