<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita_equipo extends Model
{
    use HasFactory;

    protected $table = 'cita_equipos';
    protected $fillable = [
        'cita_id',
        'equipo_id',
        'estado',
        'observacion'
    ];
}
