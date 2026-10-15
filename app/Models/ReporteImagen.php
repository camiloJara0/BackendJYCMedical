<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteImagen extends Model
{
    use HasFactory;

    protected $table = 'reporte_imagenes';

    protected $fillable = [
        'reporte_id',
        'url',
        'position',
        'descripcion',
        'tamaño'
    ];

    public function reporte()
    {
        return $this->belongsTo(Reporte::class);
    }
}
