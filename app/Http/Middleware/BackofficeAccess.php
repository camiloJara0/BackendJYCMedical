<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BackofficeAccess
{
    /**
     * Autoriza el acceso al Backoffice.
     * SuperAdmin siempre; demás roles necesitan el permiso `backoffice_access`
     * otorgado a su rol mediante rol_id + rol_has_permisos.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        if ($user->estado !== 'activo') {
            return response()->json(['success' => false, 'message' => 'Usuario suspendido'], 403);
        }

        if ($this->esSuperAdmin($user)) {
            return $next($request);
        }

        if ($this->tienePermisoBackoffice($user)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'No tienes acceso al Backoffice',
        ], 403);
    }

    public function esSuperAdmin($user): bool
    {
        return $user->rol === 'SuperAdmin';
    }

    public function tienePermisoBackoffice($user): bool
    {
        if (!$user->rol_id) {
            return false;
        }

        $rolId = (int) $user->rol_id;

        return Cache::remember("backoffice.access.rol.{$rolId}", now()->addMinutes(10), function () use ($rolId) {
            return \DB::table('rol_has_permisos')
                ->join('secciones', 'secciones.id', '=', 'rol_has_permisos.seccion_id')
                ->where('rol_has_permisos.rol_id', $rolId)
                ->where('secciones.nombre', 'backoffice_access')
                ->exists();
        });
    }
}
