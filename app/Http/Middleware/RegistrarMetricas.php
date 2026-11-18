<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrarMetricas
{
    public function handle(Request $request, Closure $next)
    {
        $inicio = microtime(true);

        $response = $next($request);

        if (!$request->is('api/*')) {
            return $response;
        }

        if (!(bool) config('backoffice.ajustes.registrar_metricas', true)) {
            return $response;
        }

        $this->registrar($request, $response, $inicio);

        return $response;
    }

    protected function registrar(Request $request, $response, float $inicio): void
    {
        $ruta = '/' . $request->path();
        $metodo = $request->method();
        $estado = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
        $duracionMs = (int) round((microtime(true) - $inicio) * 1000);

        try {
            $userId = $request->user()?->id;
        } catch (\Throwable $e) {
            $userId = null;
        }

        try {
            DB::table('metricas_api')->insert([
                'ruta' => $ruta,
                'metodo' => $metodo,
                'estado_http' => $estado,
                'duracion_ms' => $duracionMs,
                'user_id' => $userId,
                'ip' => $request->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->actualizarDiario($request, $estado, $duracionMs);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function actualizarDiario(Request $request, int $estado, int $duracionMs): void
    {
        $fecha = now()->toDateString();
        $campos = [
            'total_peticiones' => 1,
            'duracion_total_ms' => $duracionMs,
        ];

        if ($estado >= 500) {
            $campos['total_errores'] = 1;
        }

        if ($request->is('api/login') && $request->isMethod('POST')) {
            $campos['logins'] = 1;
            $campos['tokens_emitidos'] = 1;
        }

        if ($request->is('api/reporte') && $request->isMethod('POST')) {
            $campos['reportes_creados'] = 1;
        }

        if ($request->is('api/cita') && $request->isMethod('POST')) {
            $campos['citas_creadas'] = 1;
        }

        $columnas = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($campos)));
        $placeholders = implode(', ', array_fill(0, count($campos), '?'));

        $set = implode(', ', array_map(
            fn ($campo) => "`{$campo}` = `{$campo}` + ?",
            array_keys($campos)
        ));

        DB::statement(
            "INSERT INTO metricas_diarias (fecha, updated_at, {$columnas})
             VALUES (?, NOW(), {$placeholders})
             ON DUPLICATE KEY UPDATE updated_at = NOW(), {$set}",
            array_merge([$fecha], array_values($campos), array_values($campos))
        );
    }
}
