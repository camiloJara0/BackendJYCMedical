<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol_has_permiso extends Model
{
    use HasFactory;
    protected $table = 'rol_has_permisos';

    public function rol(){
        return $this->belongsTo(Rol::class, 'rol_id');
    }
    public function seccion(){
        return $this->belongsTo(Seccion::class, 'seccion_id');
    }
}
