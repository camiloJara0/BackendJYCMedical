<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Crea el rol SuperAdmin (si no existe) y el usuario SuperAdmin.
     * Credenciales desde .env: SUPERADMIN_CORREO / SUPERADMIN_PASSWORD / SUPERADMIN_NOMBRE
     */
    public function run()
    {
        $correo = env('SUPERADMIN_CORREO', 'superadmin@jycmedical.com');
        $password = env('SUPERADMIN_PASSWORD');
        $nombre = env('SUPERADMIN_NOMBRE', 'Super Admin');

        if (!$password) {
            $this->command?->warn('SUPERADMIN_PASSWORD no está definido en .env. Se omite la creación del SuperAdmin.');
            return;
        }

        $rolId = DB::table('roles')->where('nombre', 'SuperAdmin')->value('id');

        if (!$rolId) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => 'SuperAdmin',
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existe = DB::table('users')->where('correo', $correo)->exists();

        if ($existe) {
            DB::table('users')->where('correo', $correo)->update([
                'nombre' => $nombre,
                'rol' => 'SuperAdmin',
                'rol_id' => $rolId,
                'estado' => 'activo',
                'motivo_suspension' => null,
                'updated_at' => now(),
            ]);

            $this->command?->info('SuperAdmin actualizado: ' . $correo);
            return;
        }

        DB::table('users')->insert([
            'nombre' => $nombre,
            'correo' => $correo,
            'contraseña' => Hash::make($password),
            'rol' => 'SuperAdmin',
            'rol_id' => $rolId,
            'estado' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command?->info('SuperAdmin creado: ' . $correo);
    }
}
