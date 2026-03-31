<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SistemaComponenteSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1. SISTEMAS
            |--------------------------------------------------------------------------
            */
            $sistemas = [
                'Estado Físico',
                'Estado Electrónico',
                'Sistema Mecánico',
                'Sistema Hidráulico',
                'Sistema Neumático',
            ];

            $sistemaIds = [];

            foreach ($sistemas as $nombre) {
                $id = DB::table('sistemas')->insertGetId([
                    'nombre' => $nombre,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sistemaIds[$nombre] = $id;
            }

            /*
            |--------------------------------------------------------------------------
            | 2. COMPONENTES POR SISTEMA
            |--------------------------------------------------------------------------
            */
            $componentes = [

                'Estado Físico' => [
                    'condiciones ambientales',
                    'limpieza extrema',
                    'carcasa/chasis',
                    'pantalla/LCD',
                    'pilotos/testigos',
                    'indicadores/manometros',
                    'interruptores/parrillas/pulsadores',
                    'teclas/panel de control',
                    'buzzer',
                    'pintura',
                    'cable de poder',
                ],

                'Estado Electrónico' => [
                    'alimentación',
                    'motor',
                    'bomba',
                    'cableado/fibra óptica',
                    'electrodos/sonda/transductor',
                    'bombillos',
                    'conectores/terminales',
                    'transformador/resistencia',
                    'contactores/reles',
                    'tarjetas electrónicas',
                    'componentes electrónicos',
                    'fusibles/breaker',
                    'baterías',
                ],

                'Sistema Mecánico' => [
                    'rodamientos',
                    'frenos/topes',
                    'poleas/piñones',
                    'ejes/soportes',
                    'bandas/correas/cadenas',
                ],

                'Sistema Hidráulico' => [
                    'cilindros/gatos',
                    'válvulas',
                    'mangueras/tuberías',
                    'nivel aceite',
                    'nivel agua',
                    'filtros',
                    'reguladores',
                    'trampas',
                    'empaques/sellos/o-rings',
                ],

                'Sistema Neumático' => [
                    'cilindros/gatos',
                    'válvulas',
                    'floper',
                    'mangueras/tuberías',
                    'filtros',
                    'reguladores',
                    'trampas',
                    'empaques/sellos/o-rings',
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | 3. INSERT MASIVO DE COMPONENTES
            |--------------------------------------------------------------------------
            */
            $insertData = [];

            foreach ($componentes as $sistema => $items) {
                foreach ($items as $nombre) {
                    $insertData[] = [
                        'sistema_id' => $sistemaIds[$sistema],
                        'nombre' => $nombre,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('componentes')->insert($insertData);
        });
    }
}