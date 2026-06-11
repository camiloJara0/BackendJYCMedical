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
        Schema::create('cita_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('estado')->default('pendiente'); // realizado / pendiente
            $table->string('observacion')->nullable();
            $table->timestamps();
        });

        // Alterar campo equipo_id en citas para que sea nullable
        DB::statement('ALTER TABLE citas MODIFY equipo_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cita_equipos');

        // Revertir campo equipo_id a NOT NULL
        DB::statement('ALTER TABLE citas MODIFY equipo_id BIGINT UNSIGNED NOT NULL');
    }
};
