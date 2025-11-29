<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicadorAnexo extends Model
{
    use HasFactory;

    protected $table = 'indicadores_anexo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id_anexo',
        'id_indicador',
        'calificacion'
    ];

    /**
     * Relación: Un IndicadorAnexo pertenece a un Anexo05.
     */
    public function anexo05()
    {
        return $this->belongsTo(Anexo05::class, 'id_anexo');
    }

    /**
     * Relación: Un IndicadorAnexo pertenece a un IndicadorEvaluacion.
     */
    public function indicadorEvaluacion()
    {
        return $this->belongsTo(IndicadorEvaluacion::class, 'id_indicador');
    }
}