<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('nivel', 20)->default('error');
            $table->string('mensaje', 500);
            $table->text('contexto')->nullable();
            $table->text('stack_trace')->nullable();
            $table->string('archivo', 255)->nullable();
            $table->unsignedInteger('linea')->nullable();
            $table->string('ruta', 255)->nullable();
            $table->string('metodo', 10)->nullable();
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('estado_http')->nullable();
            $table->string('hash', 40)->index();
            $table->unsignedInteger('veces')->default(1);
            $table->timestamp('visto_en')->nullable();
            $table->timestamp('resuelto_en')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_errors');
    }
};
