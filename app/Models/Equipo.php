<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';

    protected $fillable = [
        'cliente_id',
        'tipo_equipo_id',
        'nombre',
        'marca',
        'modelo',
        'serie',
        'ubicacion',
        'placa',
        'registro_sanitario'
    ];
}
