<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudCita extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_citas';

    protected $fillable = [
        'NIT',
        'razon_social',
        'nombre_contacto',
        'correo',
        'telefono',
        'serial_equipo',
        'marca',
        'modelo',
        'tipo_equipo_descripcion',
        'tipo_cita',
        'motivo',
        'estado',
        'respuesta_admin',
        'archivo_respuesta',
        'fecha_respuesta',
        'cliente_id',
        'equipo_id',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}
