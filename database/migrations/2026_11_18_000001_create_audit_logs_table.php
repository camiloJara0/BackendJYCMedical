<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('nombre', 100)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('accion', 50);
            $table->string('tabla', 60)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->text('datos_anteriores')->nullable();
            $table->text('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('ruta', 255)->nullable();
            $table->unsignedSmallInteger('estado_http')->nullable();
            $table->timestamps();

            $table->index(['tabla', 'registro_id']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
