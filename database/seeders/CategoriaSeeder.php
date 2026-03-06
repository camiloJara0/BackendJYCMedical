<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categorias')->insert([
            [
                'nombre' => 'Monitores de signos vitales',
                'descripcion' => 'Equipos médicos que permiten medir y visualizar parámetros fisiológicos como frecuencia cardíaca, presión arterial, saturación de oxígeno y temperatura en tiempo real.',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sensores de oxígeno',
                'descripcion' => 'Dispositivos diseñados para medir la saturación de oxígeno en la sangre (SpO2), utilizados en monitores y equipos de terapia respiratoria.',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Transductores para ultrasonido',
                'descripcion' => 'Accesorios que convierten energía eléctrica en ondas sonoras para obtener imágenes diagnósticas mediante ultrasonido.',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Brazaletes',
                'descripcion' => 'Manguitos y brazaletes utilizados en la medición de presión arterial, compatibles con diferentes modelos de monitores.',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Cables ECG Holter',
                'descripcion' => 'Cables especializados para la conexión de electrodos en estudios de electrocardiografía continua (Holter).',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Electrocardiógrafos',
                'descripcion' => 'Equipos médicos que registran la actividad eléctrica del corazón, utilizados en diagnóstico cardiológico.',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Monitores fetales',
                'descripcion' => 'Dispositivos que permiten evaluar la frecuencia cardíaca fetal y las contracciones uterinas durante el embarazo.',
                'estado' => 'activo',
            ],
        ]);

    }
}
