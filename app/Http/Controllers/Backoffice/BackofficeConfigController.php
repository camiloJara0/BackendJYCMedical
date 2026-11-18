<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BackofficeConfigController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => config('backoffice.ajustes'),
            'entorno' => [
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'log_channel' => config('logging.default'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $actuales = config('backoffice.ajustes');

        $validacion = [];

        foreach (array_keys($actuales) as $clave) {
            $validacion[$clave] = 'sometimes';
        }

        $datos = $request->validate($validacion);

        $nuevos = array_merge($actuales, $datos);

        if (!$this->persistir($nuevos)) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron guardar los ajustes',
            ], 500);
        }

        Auditoria::registrar($request, 'actualizar_config', 'config/backoffice.php', null, $actuales, $nuevos);

        return response()->json([
            'success' => true,
            'message' => 'Ajustes guardados',
            'data' => $nuevos,
        ]);
    }

    protected function persistir(array $ajustes): bool
    {
        $archivo = config_path('backoffice.php');

        $contenido = "<?php\n\nreturn [\n\n";
        $contenido .= "    'ajustes' => [\n";

        foreach ($ajustes as $clave => $valor) {
            $claveSegura = preg_replace('/[^a-z0-9_]/i', '', $clave);

            if (is_bool($valor)) {
                $contenido .= "        '{$claveSegura}' => " . ($valor ? 'true' : 'false') . ",\n";
            } elseif (is_int($valor) || is_float($valor)) {
                $contenido .= "        '{$claveSegura}' => {$valor},\n";
            } else {
                $escape = addslashes((string) $valor);
                $contenido .= "        '{$claveSegura}' => '{$escape}',\n";
            }
        }

        $contenido .= "    ],\n\n];\n";

        $ok = File::put($archivo, $contenido);

        return $ok !== false;
    }
}
