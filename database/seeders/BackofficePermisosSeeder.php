<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackofficePermisosSeeder extends Seeder
{
    /**
     * Secciones/permisos usados por el Backoffice.
     * Idempotente: solo inserta los que falten.
     */
    public function run()
    {
        $permisos = [
            'backoffice_access',
            'backoffice_dashboard',
            'backoffice_usuarios_get',
            'backoffice_usuarios_post',
            'backoffice_usuarios_put',
            'backoffice_roles_get',
            'backoffice_roles_put',
            'backoffice_auditoria_get',
            'backoffice_errores_get',
            'backoffice_errores_put',
            'backoffice_metricas_get',
            'backoffice_logs_get',
            'backoffice_backup_get',
            'backoffice_backup_post',
            'backoffice_config_get',
            'backoffice_config_put',
            'backoffice_sesiones_get',
            'backoffice_sesiones_delete',
        ];

        $existentes = DB::table('secciones')->pluck('nombre')->all();
        $nuevos = array_values(array_diff($permisos, $existentes));

        if ($nuevos) {
            DB::table('secciones')->insert(
                array_map(fn ($nombre) => ['nombre' => $nombre], $nuevos)
            );
        }

        $this->command?->info('Permisos Backoffice: ' . count($nuevos) . ' agregados, ' . count($permisos) . ' en total.');
    }
}
