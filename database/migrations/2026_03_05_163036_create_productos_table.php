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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('cascade');
            $table->string('imagen')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo','inactivo'])->default('activo');
            $table->integer('stock')->default(0);
            $table->decimal('precio_referencial', 10, 2)->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('compatibilidad')->nullable(); 
            $table->string('tipo_conector')->nullable();
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
        Schema::dropIfExists('productos');
    }
};
