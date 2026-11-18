<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Auditoria
{
    public static function registrar(
        ?Request $request,
        string $accion,
        ?string $tabla = null,
        $registroId = null,
        $antes = null,
        $nuevo = null
    ): void {
        try {
            $user = $request?->user();

            $auditId = DB::table('audit_logs')->insertGetId([
                'user_id' => $user?->id,
                'nombre' => $user?->nombre,
                'correo' => $user?->correo,
                'accion' => $accion,
                'tabla' => $tabla,
                'registro_id' => $registroId !== null ? (int) $registroId : null,
                'datos_anteriores' => self::serializar($antes),
                'datos_nuevos' => self::serializar($nuevo),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'ruta' => $request ? '/' . $request->path() : null,
                'estado_http' => $request?->method() === 'POST' ? 201 : 200,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($tabla && $registroId !== null) {
                DB::table('backup_registros')->insert([
                    'tabla' => $tabla,
                    'registro_id' => (int) $registroId,
                    'datos' => self::serializar($antes ?? $nuevo),
                    'accion' => $accion,
                    'audit_log_id' => $auditId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected static function serializar($datos): ?string
    {
        if ($datos === null) {
            return null;
        }

        if (is_string($datos)) {
            return $datos;
        }

        if (is_object($datos) && method_exists($datos, 'toArray')) {
            $datos = $datos->toArray();
        }

        return json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
