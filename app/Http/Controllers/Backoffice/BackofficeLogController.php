<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BackofficeLogController extends Controller
{
    public function index(Request $request)
    {
        $archivo = $this->rutaLog();

        if (!File::exists($archivo)) {
            return response()->json([
                'success' => true,
                'archivo' => $archivo,
                'existe' => false,
                'lineas' => [],
            ]);
        }

        $limite = min(max((int) $request->input('lineas', 200), 1), 2000);
        $contenido = File::get($archivo);
        $todas = preg_split('/\r\n|\r|\n/', $contenido);
        $recientes = array_slice($todas, -$limite);

        $filtradas = $recientes;

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $filtradas = array_values(array_filter(
                $recientes,
                fn ($linea) => stripos($linea, $buscar) !== false
            ));
        }

        return response()->json([
            'success' => true,
            'archivo' => $archivo,
            'existe' => true,
            'tamano_bytes' => File::size($archivo),
            'ultima_modificacion' => date('Y-m-d H:i:s', File::lastModified($archivo)),
            'total_lineas' => count($todas),
            'lineas' => array_values($filtradas),
        ]);
    }

    public function limpiar(Request $request)
    {
        $archivo = $this->rutaLog();

        if (File::exists($archivo)) {
            $respaldo = $archivo . '.' . date('YmdHis') . '.bak';
            File::copy($archivo, $respaldo);
            File::put($archivo, '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Log limpiado (respaldo creado)',
        ]);
    }

    protected function rutaLog(): string
    {
        return storage_path('logs/laravel.log');
    }
}
