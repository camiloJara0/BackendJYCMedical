<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackofficeErrorController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('app_errors');

        if ($request->filled('nivel')) {
            $query->where('nivel', $request->input('nivel'));
        }

        if ($request->filled('estado')) {
            $query->where(function ($q) use ($request) {
                $request->input('estado') === 'resuelto'
                    ? $q->whereNotNull('resuelto_en')
                    : $q->whereNull('resuelto_en');
            });
        }

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('mensaje', 'like', "%{$buscar}%")
                    ->orWhere('ruta', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->input('hasta'));
        }

        $errores = $query->orderByDesc('id')
            ->paginate(min((int) $request->input('por_pagina', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $errores->items(),
            'meta' => [
                'pagina' => $errores->currentPage(),
                'por_pagina' => $errores->perPage(),
                'total' => $errores->total(),
                'ultima_pagina' => $errores->lastPage(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $error = DB::table('app_errors')->find($id);

        if (!$error) {
            return response()->json(['success' => false, 'message' => 'Error no encontrado'], 404);
        }

        foreach (['contexto', 'stack_trace'] as $campo) {
            if ($error->{$campo}) {
                $decoded = json_decode($error->{$campo}, true);
                if ($decoded !== null) {
                    $error->{$campo} = $decoded;
                }
            }
        }

        return response()->json(['success' => true, 'data' => $error]);
    }

    public function resolver(Request $request, int $id)
    {
        $error = DB::table('app_errors')->find($id);

        if (!$error) {
            return response()->json(['success' => false, 'message' => 'Error no encontrado'], 404);
        }

        $antes = ['resuelto_en' => $error->resuelto_en];
        DB::table('app_errors')->where('id', $id)->update([
            'resuelto_en' => now(),
            'updated_at' => now(),
        ]);

        Auditoria::registrar($request, 'resolver_error', 'app_errors', $id, $antes, ['resuelto_en' => now()]);

        return response()->json(['success' => true, 'message' => 'Error marcado como resuelto']);
    }

    public function reabrir(Request $request, int $id)
    {
        $error = DB::table('app_errors')->find($id);

        if (!$error) {
            return response()->json(['success' => false, 'message' => 'Error no encontrado'], 404);
        }

        DB::table('app_errors')->where('id', $id)->update([
            'resuelto_en' => null,
            'updated_at' => now(),
        ]);

        Auditoria::registrar($request, 'reabrir_error', 'app_errors', $id, ['resuelto_en' => $error->resuelto_en], null);

        return response()->json(['success' => true, 'message' => 'Error reabierto']);
    }

    public function resumen()
    {
        $pendientes = DB::table('app_errors')->whereNull('resuelto_en')->count();
        $resueltos = DB::table('app_errors')->whereNotNull('resuelto_en')->count();

        $porMensaje = DB::table('app_errors')
            ->whereNull('resuelto_en')
            ->selectRaw('hash, MAX(mensaje) as mensaje, MAX(ruta) as ruta, SUM(veces) as veces, MAX(created_at) as ultimo')
            ->groupBy('hash')
            ->orderByDesc('veces')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'pendientes' => $pendientes,
            'resueltos' => $resueltos,
            'top' => $porMensaje,
        ]);
    }
}
