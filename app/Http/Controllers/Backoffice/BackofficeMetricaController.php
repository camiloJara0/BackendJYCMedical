<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BackofficeMetricaController extends Controller
{
    public function index(Request $request)
    {
        $dias = min(max((int) $request->input('dias', 7), 1), 90);
        $desde = now()->subDays($dias)->toDateString();

        $diarias = DB::table('metricas_diarias')
            ->where('fecha', '>=', $desde)
            ->orderBy('fecha')
            ->get();

        $totales = [
            'peticiones' => (int) $diarias->sum('total_peticiones'),
            'errores' => (int) $diarias->sum('total_errores'),
            'logins' => (int) $diarias->sum('logins'),
            'reportes' => (int) $diarias->sum('reportes_creados'),
            'citas' => (int) $diarias->sum('citas_creadas'),
            'duracion_promedio_ms' => 0,
        ];

        if ($totales['peticiones'] > 0) {
            $totales['duracion_promedio_ms'] = (int) round($diarias->sum('duracion_total_ms') / $totales['peticiones']);
        }

        $lentas = DB::table('metricas_api')
            ->where('created_at', '>=', now()->subDays($dias))
            ->orderByDesc('duracion_ms')
            ->limit(15)
            ->get(['ruta', 'metodo', 'estado_http', 'duracion_ms', 'created_at']);

        $fallos = DB::table('metricas_api')
            ->where('created_at', '>=', now()->subDays($dias))
            ->where('estado_http', '>=', 500)
            ->orderByDesc('id')
            ->limit(15)
            ->get(['ruta', 'metodo', 'estado_http', 'duracion_ms', 'created_at']);

        return response()->json([
            'success' => true,
            'rango_dias' => $dias,
            'diarias' => $diarias,
            'totales' => $totales,
            'lentas' => $lentas,
            'fallos' => $fallos,
        ]);
    }

    public function rutas(Request $request)
    {
        $dias = min(max((int) $request->input('dias', 7), 1), 90);

        $rutas = DB::table('metricas_api')
            ->where('created_at', '>=', now()->subDays($dias))
            ->selectRaw('ruta, metodo,
                COUNT(*) as total,
                AVG(duracion_ms) as promedio_ms,
                MAX(duracion_ms) as maximo_ms,
                SUM(CASE WHEN estado_http >= 500 THEN 1 ELSE 0 END) as fallos,
                SUM(CASE WHEN estado_http >= 400 AND estado_http < 500 THEN 1 ELSE 0 END) as rechazos')
            ->groupBy('ruta', 'metodo')
            ->orderByDesc('total')
            ->limit(50)
            ->get();

        return response()->json(['success' => true, 'data' => $rutas]);
    }

    public function sesiones()
    {
        $tokens = DB::table('personal_access_tokens')
            ->leftJoin('users', 'users.id', '=', 'personal_access_tokens.tokenable_id')
            ->where('personal_access_tokens.tokenable_type', 'App\\Models\\User')
            ->whereNull('personal_access_tokens.expires_at')
            ->orWhere(function ($q) {
                $q->where('personal_access_tokens.tokenable_type', 'App\\Models\\User')
                    ->where('personal_access_tokens.expires_at', '>', now());
            })
            ->orderByDesc('personal_access_tokens.last_used_at')
            ->limit(100)
            ->get([
                'personal_access_tokens.id',
                'personal_access_tokens.name',
                'personal_access_tokens.ip_address',
                'personal_access_tokens.user_agent',
                'personal_access_tokens.last_used_at',
                'personal_access_tokens.expires_at',
                'users.nombre',
                'users.correo',
                'users.rol',
            ]);

        $total = DB::table('personal_access_tokens')
            ->where('expires_at', '>', now())
            ->count();

        $expiradosHoy = DB::table('personal_access_tokens')
            ->whereDate('expires_at', now()->toDateString())
            ->count();

        return response()->json([
            'success' => true,
            'total_activos' => $total,
            'expirados_hoy' => $expiradosHoy,
            'data' => $tokens,
        ]);
    }

    public function podarSesiones(Request $request)
    {
        $eliminados = DB::table('personal_access_tokens')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '<', now());
            })
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$eliminados} token(es) expirado(s) eliminado(s)",
        ]);
    }
}
