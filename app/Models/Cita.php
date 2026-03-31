<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'tecnico_id',
        'cliente_id',
        'equipo_id',
        'tipo',
        'fecha',
        'hora',
        'estado'
    ];
}
