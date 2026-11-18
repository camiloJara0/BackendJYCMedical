<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ErroresClienteController extends Controller
{
    /**
     * Recepción de errores del cliente (Nuxt). Público con throttle.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'mensaje' => 'required|string|max:500',
            'nivel' => 'sometimes|in:info,warning,error',
            'ruta' => 'sometimes|nullable|string|max:255',
            'metodo' => 'sometimes|nullable|string|max:10',
            'stack' => 'sometimes|nullable|string|max:10000',
            'contexto' => 'sometimes|nullable|string|max:5000',
            'archivo' => 'sometimes|nullable|string|max:255',
            'linea' => 'sometimes|nullable|integer',
        ]);

        $hash = sha1($datos['mensaje'] . '|' . ($datos['ruta'] ?? ''));

        $existente = DB::table('app_errors')->where('hash', $hash)->first();

        if ($existente) {
            DB::table('app_errors')->where('id', $existente->id)->update([
                'veces' => $existente->veces + 1,
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'registrado' => true,
                'agrupado' => true,
                'id' => $existente->id,
            ], 202);
        }

        $id = DB::table('app_errors')->insertGetId([
            'user_id' => $request->user()?->id,
            'nivel' => $datos['nivel'] ?? 'error',
            'mensaje' => $datos['mensaje'],
            'contexto' => $datos['contexto'] ?? null,
            'stack_trace' => $datos['stack'] ?? null,
            'archivo' => $datos['archivo'] ?? null,
            'linea' => $datos['linea'] ?? null,
            'ruta' => $datos['ruta'] ?? null,
            'metodo' => $datos['metodo'] ?? $request->method(),
            'ip' => $request->ip(),
            'estado_http' => null,
            'hash' => $hash,
            'veces' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'registrado' => true,
            'agrupado' => false,
            'id' => $id,
        ], 201);
    }
}
