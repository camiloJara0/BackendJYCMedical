<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('backup_registros', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 60);
            $table->unsignedBigInteger('registro_id');
            $table->text('datos')->nullable();
            $table->string('accion', 20);
            $table->unsignedBigInteger('audit_log_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['tabla', 'registro_id', 'id']);
            $table->index(['tabla', 'registro_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('backup_registros');
    }
};
