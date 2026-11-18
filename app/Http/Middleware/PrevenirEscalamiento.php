<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrevenirEscalamiento
{
    /**
     * Impide que el rol SuperAdmin se asigne, modifique o elimine por HTTP/CRUD.
     * SuperAdmin solo se crea/actualiza desde seeders.
     */
    public function handle(Request $request, Closure $next)
    {
        $prohibido = ['SuperAdmin'];

        if ($request->filled('rol') && in_array((string) $request->input('rol'), $prohibido, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol SuperAdmin no se puede asignar por API',
            ], 403);
        }

        if ($request->filled('nombre_rol') && in_array((string) $request->input('nombre_rol'), $prohibido, true)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol SuperAdmin no se puede crear por API',
            ], 403);
        }

        if ($request->filled('rol_id')) {
            $esSuperAdmin = \DB::table('roles')
                ->where('id', $request->input('rol_id'))
                ->where('nombre', 'SuperAdmin')
                ->exists();

            if ($esSuperAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol SuperAdmin no se puede asignar por API',
                ], 403);
            }
        }

        $targetId = $this->idDeUsuarioEnRuta($request);

        if ($targetId) {
            $objetivoEsSuperAdmin = \DB::table('users')
                ->where('id', $targetId)
                ->where('rol', 'SuperAdmin')
                ->exists();

            if ($objetivoEsSuperAdmin) {
                $actorEsSuperAdmin = $request->user() && $request->user()->rol === 'SuperAdmin';

                if (!$actorEsSuperAdmin) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un SuperAdmin solo puede ser gestionado desde seeders',
                    ], 403);
                }

                if (in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Un SuperAdmin solo puede ser gestionado desde seeders',
                    ], 403);
                }
            }
        }

        return $next($request);
    }

    protected function idDeUsuarioEnRuta(Request $request): ?int
    {
        if (!$request->is('api/backoffice/usuarios*') && !$request->is('api/usuarios*')) {
            return null;
        }

        $ruta = $request->route();

        if (!$ruta) {
            return null;
        }

        foreach ($ruta->parameters() as $valor) {
            if (is_numeric($valor) && (int) $valor > 0) {
                return (int) $valor;
            }
        }

        return null;
    }
}
