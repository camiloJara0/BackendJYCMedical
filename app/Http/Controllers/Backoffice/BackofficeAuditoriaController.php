<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackofficeAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('audit_logs');

        foreach (['accion', 'tabla', 'user_id', 'correo'] as $campo) {
            if ($request->filled($campo)) {
                $query->where($campo, $request->input($campo));
            }
        }

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%")
                    ->orWhere('accion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        $registros = $query->orderByDesc('id')
            ->paginate(min((int) $request->input('por_pagina', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $registros->items(),
            'meta' => [
                'pagina' => $registros->currentPage(),
                'por_pagina' => $registros->perPage(),
                'total' => $registros->total(),
                'ultima_pagina' => $registros->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $registro = DB::table('audit_logs')->find($id);

        if (!$registro) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado'], 404);
        }

        $respaldo = DB::table('backup_registros')
            ->where('audit_log_id', $id)
            ->orderByDesc('id')
            ->first();

        if ($respaldo && $respaldo->datos) {
            $respaldo->datos = json_decode($respaldo->datos, true);
        }

        foreach (['datos_anteriores', 'datos_nuevos'] as $campo) {
            if ($registro->{$campo}) {
                $registro->{$campo} = json_decode($registro->{$campo}, true);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $registro,
            'respaldo' => $respaldo,
        ]);
    }

    public function respaldo(Request $request, int $id)
    {
        $respaldo = DB::table('backup_registros')->where('audit_log_id', $id)->orderByDesc('id')->first();

        if (!$respaldo) {
            return response()->json([
                'success' => false,
                'message' => 'No hay respaldo para esta operación',
            ], 404);
        }

        $datos = json_decode($respaldo->datos, true);

        if (!$datos) {
            return response()->json([
                'success' => false,
                'message' => 'El respaldo no contiene datos restaurables',
            ], 422);
        }

        $tabla = $respaldo->tabla;

        if (!\Schema::hasTable($tabla)) {
            return response()->json(['success' => false, 'message' => 'Tabla inexistente'], 422);
        }

        $columnas = \Schema::getColumnListing($tabla);
        $rellenables = array_intersect_key($datos, array_flip($columnas));

        if (empty($rellenables)) {
            return response()->json(['success' => false, 'message' => 'Nada que restaurar'], 422);
        }

        $existe = DB::table($tabla)->where('id', $respaldo->registro_id)->exists();

        if ($existe) {
            DB::table($tabla)->where('id', $respaldo->registro_id)->update($rellenables);
        } else {
            $rellenables['id'] = $respaldo->registro_id;
            DB::table($tabla)->insert($rellenables);
        }

        Auditoria::registrar($request, 'restaurar', $tabla, $respaldo->registro_id, null, $rellenables);

        return response()->json([
            'success' => true,
            'message' => 'Registro restaurado',
            'data' => $rellenables,
        ]);
    }

    public function acciones()
    {
        $acciones = DB::table('audit_logs')->distinct()->orderBy('accion')->pluck('accion');

        return response()->json(['success' => true, 'data' => $acciones]);
    }
}
