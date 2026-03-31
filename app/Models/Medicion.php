<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicion extends Model
{
    use HasFactory;

    protected $table = 'mediciones';

    protected $fillable = [
        'reporte_id',
        'unidad',
        'variable',
        'valor_medido',
        'valor_esperado'
    ];
}
