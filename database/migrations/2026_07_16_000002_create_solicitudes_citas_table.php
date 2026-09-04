<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('solicitudes_citas', function (Blueprint $table) {
            $table->id();
            $table->string('NIT')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('nombre_contacto');
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->string('serial_equipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('tipo_equipo_descripcion')->nullable();
            $table->enum('tipo_cita', ['mantenimiento', 'revision', 'reparacion', 'otro'])->default('mantenimiento');
            $table->text('motivo')->nullable();
            $table->enum('estado', ['pendiente', 'en_revision', 'atendida', 'rechazada', 'convertida_cita'])->default('pendiente');
            $table->text('respuesta_admin')->nullable();
            $table->string('archivo_respuesta')->nullable();
            $table->dateTime('fecha_respuesta')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('solicitudes_citas');
    }
};
