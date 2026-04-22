<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use HasFactory;

    protected $table = 'reportes';

    protected $fillable = [
        'cita_id',
        'tecnico_id',
        'cliente_id',
        'equipo_id',
        'tipo',
        'fecha',
        'estado'
    ];

    public function actividades()
    {
        return $this->hasMany(Actividad::class);
    }

    public function materiales()
    {
        return $this->hasMany(Material::class);
    }

    public function accesorios()
    {
        return $this->hasMany(Accesorio::class);
    }

    public function mediciones()
    {
        return $this->hasMany(Medicion::class);
    }

    public function repuestos()
    {
        return $this->hasMany(Repuesto::class);
    }

    public function estado_componente()
    {
        return $this->hasMany(Estado_componente::class);
    }

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function firmaRecibido() {
        return $this->hasOne(Recibido_firma::class);
    }

    public function historialEstadosReporte()
    {
        return $this->hasMany(Historial_estados_reporte::class);
    }

}
