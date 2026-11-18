<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metricas_diarias', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->unsignedInteger('total_peticiones')->default(0);
            $table->unsignedInteger('total_errores')->default(0);
            $table->unsignedInteger('duracion_total_ms')->default(0);
            $table->unsignedInteger('usuarios_activos')->default(0);
            $table->unsignedInteger('logins')->default(0);
            $table->unsignedInteger('reportes_creados')->default(0);
            $table->unsignedInteger('citas_creadas')->default(0);
            $table->unsignedInteger('tokens_emitidos')->default(0);
            $table->unsignedInteger('tokens_revocados')->default(0);
            $table->unsignedInteger('jobs_ejecutados')->default(0);
            $table->unsignedInteger('jobs_fallidos')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('metricas_diarias');
    }
};
