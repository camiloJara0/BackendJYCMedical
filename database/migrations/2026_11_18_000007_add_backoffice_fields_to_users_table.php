<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('rol_id')->nullable()->after('rol');
            $table->timestamp('ultimo_acceso_at')->nullable()->after('estado');
            $table->string('motivo_suspension', 250)->nullable()->after('ultimo_acceso_at');

            $table->foreign('rol_id')->references('id')->on('roles')->nullOnDelete();
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('name');
            $table->string('user_agent', 255)->nullable()->after('ip_address');
        });
    }

    public function down()
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rol_id']);
            $table->dropColumn(['rol_id', 'ultimo_acceso_at', 'motivo_suspension']);
        });
    }
};
