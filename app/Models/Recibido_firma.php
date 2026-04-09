<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recibido_firma extends Model
{
    use HasFactory;

    protected $table = 'recibido_firmas';

    protected $fillable = [
        'nombre',
        'cargo',
        'firma',
        'reporte_id',
    ];
}
