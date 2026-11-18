<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasFactory, HasApiTokens;

    protected $table = 'users';

    protected $fillable = [
        'nombre',
        'correo',
        'contraseña',
        'rol',
        'rol_id',
        'estado',
        'ultimo_acceso_at',
        'motivo_suspension',
    ];

    protected $hidden = [
        'contraseña',
    ];

    public function rolModel()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
}
