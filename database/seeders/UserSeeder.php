<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'nombre' => 'Administrador General',
                'correo' => 'admin@jycmedical.com',
                'contraseña' => Hash::make('password'),
                'rol' => 'Admin',
                'estado' => 'activo',
            ],
        ]);
    }
}
