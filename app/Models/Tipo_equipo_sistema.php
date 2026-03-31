<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipo_equipo_sistema extends Model
{
    use HasFactory;

    protected $table = 'tipo_equipo_sistemas';

    protected $fillable = [
        'tipo_equipo_id',
        'sistema_id',
    ];
}
