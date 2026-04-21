<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historial_estados_cita extends Model
{
    use HasFactory;
    protected $table = 'historial_estados_citas';
    protected $fillable = [
        'cita_id',
        'tecnico_id',
        'nombre_estado',
        'observaciones',
    ];
}
