<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metricas_api', function (Blueprint $table) {
            $table->id();
            $table->string('ruta', 255);
            $table->string('metodo', 10);
            $table->unsignedSmallInteger('estado_http');
            $table->unsignedInteger('duracion_ms')->default(0);
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['ruta', 'metodo']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('metricas_api');
    }
};
