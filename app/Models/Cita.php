<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';

    protected $fillable = [
        'tecnico_id',
        'cliente_id',
        'equipo_id',
        'tipo',
        'fecha',
        'hora',
        'estado'
    ];

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class, 'tecnico_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'equipo_id');
    }

    public function historial_estados()
    {
        return $this->hasMany(Historial_estados_cita::class, 'cita_id');
    }

    public function ultimo_estado()
    {
        return $this->hasOne(Historial_estados_cita::class, 'cita_id')->latestOfMany();
    }

    public function ediciones()
    {
        return $this->hasMany(Historial_estados_cita::class, 'cita_id');
    }

    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'cita_equipos', 'cita_id', 'equipo_id')
                    ->withPivot('estado', 'observacion')
                    ->withTimestamps();
    }
}
