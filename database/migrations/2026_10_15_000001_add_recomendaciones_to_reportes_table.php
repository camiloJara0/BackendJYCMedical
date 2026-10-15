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
        Schema::table('reportes', function (Blueprint $table) {
            $table->text('recomendaciones_cliente')->nullable()->after('estado');
            $table->text('riesgos_identificados')->nullable()->after('recomendaciones_cliente');
            $table->text('proximas_acciones')->nullable()->after('riesgos_identificados');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn([
                'recomendaciones_cliente',
                'riesgos_identificados',
                'proximas_acciones',
            ]);
        });
    }
};
