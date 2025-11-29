<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anexo04 extends Model
{
    use HasFactory;

    protected $table = 'anexos_04';
    protected $primaryKey = 'id';
    public $timestamps = false;

    
    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'problemas_detectados',
        'observaciones',
        'id_empresa',
        'id_EFSRT'
    ];
    
    /**
     * Propiedad para castear atributos a tipos de datos nativos.
     * Laravel convertirá automáticamente estos campos en objetos Carbon.
     */
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relación: Un Anexo04 pertenece a una Empresa (opcional).
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    /**
     * Relación: Un Anexo04 pertenece a una EFSRT (opcional).
     */
    public function efsrt()
    {
        return $this->belongsTo(EFSRT::class, 'id_EFSRT');
    }

    /**
     * Relación: Un Anexo04 puede tener muchas VisitasAnexo04.
     */
    public function visitas()
    {
        return $this->hasMany(VisitaAnexo04::class, 'idAnexo');
    }
}