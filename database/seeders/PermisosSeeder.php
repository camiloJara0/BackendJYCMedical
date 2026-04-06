<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $secciones = [
            'Agenda','Categorias','Clientes','Componentes','Cotizaciones','Equipos','Reportes',
            'Sistemas','Tipo Equipos','Tecnicos','Productos'
        ];

        $acciones = ['_get', '_post', '_put', '_delete'];

        $permisos = [];

        foreach ($secciones as $seccion) {
            $clave = str_replace(' ', '_', $seccion);
            foreach ($acciones as $accion) {
                $permisos[] = [
                    'nombre' => $clave . $accion,
                ];
            }
        }

        DB::table('secciones')->insert($permisos);
    }
}
