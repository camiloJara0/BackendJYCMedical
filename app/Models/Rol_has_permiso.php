<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol_has_permiso extends Model
{
    use HasFactory;
    protected $table = 'rol_has_permisos';

    public function tecnico(){
        return $this->belongsTo(Tecnico::class, 'rol_id');
    }
    public function seccion(){
        return $this->belongsTo(Secciones::class, 'seccion_id');
    }
}
