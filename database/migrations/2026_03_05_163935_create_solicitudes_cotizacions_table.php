<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitudes_cotizacions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('correo');
            $table->string('NIT')->nullable();
            $table->string('telefono')->nullable();
            $table->string('empresa')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagenes_referencia')->nullable();
            $table->enum('estado', ['pendiente','atendida','rechazada'])->default('pendiente');
            $table->dateTime('fecha_respuesta')->nullable();
            $table->text('observaciones_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('solicitudes_cotizacions');
    }
};
