<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicadorEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'indicadores_evaluacion';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'item',
        'nombre',
        'id_tipo_indicador',
        'estado'
    ];

    /**
     * Relación: Un IndicadorEvaluacion pertenece a un TipoIndicador.
     */
    public function tipoIndicador()
    {
        return $this->belongsTo(TipoIndicador::class, 'id_tipo_indicador');
    }

    /**
     * Relación: Un IndicadorEvaluacion puede estar en muchos IndicadoresAnexo.
     */
    public function indicadoresAnexo()
    {
        return $this->hasMany(IndicadorAnexo::class, 'id_indicador');
    }
}