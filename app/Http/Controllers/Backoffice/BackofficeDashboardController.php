<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BackofficeDashboardController extends Controller
{
    public function index(Request $request)
    {
        $hoy = now()->toDateString();
        $ayer = now()->subDay()->toDateString();

        $usuariosTotales = DB::table('users')->count();
        $usuariosActivos = DB::table('users')->where('estado', 'activo')->count();
        $tecnicos = DB::table('users')->where('rol', 'Tecnico')->count();
        $admins = DB::table('users')->where('rol', 'Admin')->count();
        $superadmins = DB::table('users')->where('rol', 'SuperAdmin')->count();

        $sesionesActivas = DB::table('personal_access_tokens')
            ->where('expires_at', '>', now())
            ->count();

        $reportesHoy = DB::table('reportes')->whereDate('created_at', $hoy)->count();
        $reportesTotales = DB::table('reportes')->count();
        $citasHoy = DB::table('citas')->whereDate('created_at', $hoy)->count();
        $citasTotales = DB::table('citas')->count();
        $clientes = DB::table('clientes')->count();
        $equipos = DB::table('equipos')->count();

        $metricaHoy = DB::table('metricas_diarias')->where('fecha', $hoy)->first();
        $metricaAyer = DB::table('metricas_diarias')->where('fecha', $ayer)->first();

        $erroresPendientes = DB::table('app_errors')->whereNull('resuelto_en')->count();
        $erroresHoy = DB::table('app_errors')->whereDate('created_at', $hoy)->count();

        $peticionesHoy = (int) ($metricaHoy->total_peticiones ?? 0);
        $peticionesAyer = (int) ($metricaAyer->total_peticiones ?? 0);

        $variacion = $peticionesAyer > 0
            ? round((($peticionesHoy - $peticionesAyer) / $peticionesAyer) * 100, 1)
            : null;

        $ultimosAcciones = DB::table('audit_logs')
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'nombre', 'correo', 'accion', 'tabla', 'registro_id', 'created_at']);

        $erroresRecientes = DB::table('app_errors')
            ->whereNull('resuelto_en')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'nivel', 'mensaje', 'ruta', 'veces', 'created_at']);

        $actividadPorDia = DB::table('metricas_diarias')
            ->where('fecha', '>=', now()->subDays(13)->toDateString())
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'success' => true,
            'usuarios' => [
                'total' => $usuariosTotales,
                'activos' => $usuariosActivos,
                'tecnicos' => $tecnicos,
                'admins' => $admins,
                'superadmins' => $superadmins,
                'sesiones_activas' => $sesionesActivas,
            ],
            'operacion' => [
                'reportes_hoy' => $reportesHoy,
                'reportes_total' => $reportesTotales,
                'citas_hoy' => $citasHoy,
                'citas_total' => $citasTotales,
                'clientes' => $clientes,
                'equipos' => $equipos,
            ],
            'rendimiento' => [
                'peticiones_hoy' => $peticionesHoy,
                'errores_hoy' => (int) ($metricaHoy->total_errores ?? 0),
                'logins_hoy' => (int) ($metricaHoy->logins ?? 0),
                'duracion_promedio_ms' => $peticionesHoy > 0
                    ? (int) round($metricaHoy->duracion_total_ms / $peticionesHoy)
                    : 0,
                'variacion_vs_ayer' => $variacion,
            ],
            'errores' => [
                'pendientes' => $erroresPendientes,
                'hoy' => $erroresHoy,
            ],
            'ultimas_acciones' => $ultimosAcciones,
            'errores_recientes' => $erroresRecientes,
            'actividad_14_dias' => $actividadPorDia,
        ]);
    }

    public function salud()
    {
        $checks = [];

        $checks['base_datos'] = $this->verificar(
            'Conexión a base de datos',
            fn () => DB::select('SELECT 1') !== null
        );

        $checks['tablas_backoffice'] = $this->verificar(
            'Tablas Backoffice presentes',
            fn () => \Schema::hasTable('audit_logs')
                && \Schema::hasTable('app_errors')
                && \Schema::hasTable('metricas_api')
                && \Schema::hasTable('metricas_diarias')
                && \Schema::hasTable('backup_registros')
        );

        $checks['jobs'] = $this->verificar(
            'Tablas de cola (jobs/failed_jobs)',
            fn () => \Schema::hasTable('jobs') && \Schema::hasTable('failed_jobs')
        );

        $checks['almacenamiento'] = $this->verificar(
            'Almacenamiento escribible',
            fn () => is_writable(storage_path('logs'))
        );

        $checks['log'] = $this->verificar(
            'Tamaño del log bajo 20 MB',
            fn () => !file_exists(storage_path('logs/laravel.log'))
                || filesize(storage_path('logs/laravel.log')) < 20 * 1024 * 1024
        );

        $checks['cola'] = $this->verificar(
            'Cola configurada',
            fn () => config('queue.default') !== null
        );

        $fallidos = count(array_filter($checks, fn ($c) => !$c['ok']));

        return response()->json([
            'success' => true,
            'estado' => $fallidos === 0 ? 'saludable' : 'con_incidencias',
            'incidencias' => $fallidos,
            'checks' => $checks,
            'generado_en' => now()->toDateTimeString(),
        ]);
    }

    protected function verificar(string $nombre, callable $fn): array
    {
        try {
            $ok = (bool) $fn();
        } catch (\Throwable $e) {
            $ok = false;
        }

        return ['nombre' => $nombre, 'ok' => $ok];
    }
}
