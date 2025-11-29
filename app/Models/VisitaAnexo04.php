<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitaAnexo04 extends Model
{
    use HasFactory;

    protected $table = 'visitas_anexo04';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'numero',
        'fecha',
        'tareas',
        'porcentaje_avance',
        'idAnexo',
        'foto1',
        'foto2',
        'foto3'
    ];

    /**
     * Relación: Una VisitaAnexo04 pertenece a un Anexo04.
     */
    public function anexo04()
    {
        return $this->belongsTo(Anexo04::class, 'idAnexo');
    }
}