<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado_componente extends Model
{
    use HasFactory;

    protected $table = 'estado_componentes';

    protected $fillable = [
        'reporte_id',
        'componente_id',
        'estado',
        'observacion'
    ];

    public function componente()
    {
        return $this->belongsTo(Componente::class);
    }
}
