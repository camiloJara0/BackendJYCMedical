<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class solicitudes_cotizacion extends Model
{
    use HasFactory;
    protected $table = 'solicitudes_cotizacions';

    protected $fillable = [
        'nombre',
        'correo',
        'NIT',
        'telefono',
        'empresa',
        'descripcion',
        'imagenes_referencia',
        'estado',
        'fecha_respuesta',
        'observaciones_admin',
    ];

}
