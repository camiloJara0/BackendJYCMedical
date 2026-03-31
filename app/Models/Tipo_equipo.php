<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipo_equipo extends Model
{
    use HasFactory;

    protected $table = 'tipo_equipos';

    protected $fillable = [
        'nombre',
    ];

    public function sistemas()
    {
        return $this->belongsToMany(Sistema::class, 'tipo_equipo_sistemas');
    }

}
