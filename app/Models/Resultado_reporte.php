<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resultado_reporte extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporte_id',
        'estado',
        'observacion',
    ];

    
}
