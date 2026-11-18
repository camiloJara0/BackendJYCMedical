<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackofficeBackupController extends Controller
{
    public function index()
    {
        $tablas = DB::select('SHOW TABLES');

        $lista = [];

        foreach ($tablas as $fila) {
            $nombre = reset($fila);
            $filas = (int) DB::table($nombre)->count();

            $ultima = DB::table('backup_registros')
                ->where('tabla', $nombre)
                ->orderByDesc('id')
                ->value('created_at');

            $lista[] = [
                'tabla' => $nombre,
                'filas' => $filas,
                'ultimo_respaldo' => $ultima,
            ];
        }

        $respaldos = DB::table('backup_registros')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'tabla', 'registro_id', 'accion', 'created_at']);

        return response()->json([
            'success' => true,
            'tablas' => $lista,
            'respaldos' => $respaldos,
            'total_respaldos' => DB::table('backup_registros')->count(),
        ]);
    }

    public function exportar(Request $request)
    {
        $datos = $request->validate([
            'tabla' => 'required|string',
        ]);

        $tabla = $datos['tabla'];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $tabla) || !\Schema::hasTable($tabla)) {
            return response()->json(['success' => false, 'message' => 'Tabla inválida'], 422);
        }

        $filas = DB::table($tabla)->limit(50000)->get()->map(fn ($f) => (array) $f)->all();

        Auditoria::registrar($request, 'exportar', $tabla, null, null, ['filas' => count($filas)]);

        return response()->json([
            'success' => true,
            'tabla' => $tabla,
            'filas' => count($filas),
            'datos' => $filas,
        ]);
    }

    public function guardarRespaldo(Request $request)
    {
        $datos = $request->validate([
            'tabla' => 'required|string',
            'registro_id' => 'required|integer',
        ]);

        $tabla = $datos['tabla'];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $tabla) || !\Schema::hasTable($tabla)) {
            return response()->json(['success' => false, 'message' => 'Tabla inválida'], 422);
        }

        $fila = DB::table($tabla)->where('id', $datos['registro_id'])->first();

        if (!$fila) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], 404);
        }

        Auditoria::registrar($request, 'respaldo_manual', $tabla, $datos['registro_id'], $fila, null);

        return response()->json([
            'success' => true,
            'message' => 'Respaldo guardado',
        ], 201);
    }

    public function poda(Request $request)
    {
        $datos = $request->validate([
            'dias' => 'required|integer|min:1|max:730',
        ]);

        $eliminados = DB::table('backup_registros')
            ->where('created_at', '<', now()->subDays($datos['dias']))
            ->delete();

        Auditoria::registrar($request, 'poda_respaldos', 'backup_registros', null, ['dias' => $datos['dias']], ['eliminados' => $eliminados]);

        return response()->json([
            'success' => true,
            'message' => "{$eliminados} respaldo(s) eliminado(s)",
        ]);
    }

    public function archivos()
    {
        $ruta = storage_path('app/backups');

        if (!File::isDirectory($ruta)) {
            File::makeDirectory($ruta, 0755, true);
        }

        $archivos = collect(File::files($ruta))->map(fn ($f) => [
            'nombre' => $f->getFilename(),
            'tamano_bytes' => $f->getSize(),
            'modificado' => date('Y-m-d H:i:s', $f->getMTime()),
        ])->sortByDesc('modificado')->values();

        return response()->json(['success' => true, 'ruta' => $ruta, 'data' => $archivos]);
    }
}
