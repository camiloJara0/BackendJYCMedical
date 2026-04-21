<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historial_estados_reporte extends Model
{
    use HasFactory;

    protected $table = 'historial_estados_reportes';

    protected $fillable = [
        'reporte_id',
        'tecnico_id',
        'nombre_estado',
        'observaciones',
    ];
}
