<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class BackofficeUsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('rolModel');

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('correo', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->input('rol'));
        }

        $usuarios = $query->orderBy('id', 'desc')
            ->paginate(min((int) $request->input('por_pagina', 15), 100));

        return response()->json([
            'success' => true,
            'data' => $usuarios->items(),
            'meta' => [
                'pagina' => $usuarios->currentPage(),
                'por_pagina' => $usuarios->perPage(),
                'total' => $usuarios->total(),
                'ultima_pagina' => $usuarios->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'correo' => ['required', 'email', 'max:150', Rule::unique('users', 'correo')],
            'contraseña' => 'required|string|min:6|max:100',
            'rol' => ['required', 'string', Rule::in(['Admin', 'Tecnico'])],
            'rol_id' => 'nullable|integer|exists:roles,id',
            'estado' => 'sometimes|in:activo,inactivo',
        ]);

        $usuario = User::create([
            'nombre' => $datos['nombre'],
            'correo' => $datos['correo'],
            'contraseña' => Hash::make($datos['contraseña']),
            'rol' => $datos['rol'],
            'rol_id' => $datos['rol_id'] ?? null,
            'estado' => $datos['estado'] ?? 'activo',
        ]);

        Auditoria::registrar($request, 'crear', 'users', $usuario->id, null, $usuario->only(['nombre', 'correo', 'rol', 'estado']));

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado',
            'data' => $usuario->only(['id', 'nombre', 'correo', 'rol', 'rol_id', 'estado']),
        ], 201);
    }

    public function show(User $user)
    {
        $user->load('rolModel');

        return response()->json([
            'success' => true,
            'data' => $user,
            'permisos' => $this->permisosDeRol($user->rol_id),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $datos = $request->validate([
            'nombre' => 'sometimes|string|max:150',
            'correo' => ['sometimes', 'email', 'max:150', Rule::unique('users', 'correo')->ignore($user->id)],
            'rol' => ['sometimes', 'string', Rule::in(['Admin', 'Tecnico'])],
            'rol_id' => 'nullable|integer|exists:roles,id',
            'estado' => 'sometimes|in:activo,inactivo',
            'contraseña' => 'nullable|string|min:6|max:100',
        ]);

        $antes = $user->only(['nombre', 'correo', 'rol', 'rol_id', 'estado']);

        if (!empty($datos['contraseña'])) {
            $datos['contraseña'] = Hash::make($datos['contraseña']);
        } else {
            unset($datos['contraseña']);
        }

        $user->fill($datos)->save();

        Cache::forget("backoffice.access.rol.{$user->rol_id}");

        Auditoria::registrar($request, 'actualizar', 'users', $user->id, $antes, $user->only(['nombre', 'correo', 'rol', 'rol_id', 'estado']));

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado',
            'data' => $user->only(['id', 'nombre', 'correo', 'rol', 'rol_id', 'estado']),
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->rol === 'SuperAdmin') {
            return response()->json([
                'success' => false,
                'message' => 'Un SuperAdmin no se puede eliminar',
            ], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminarte a ti mismo',
            ], 403);
        }

        $antes = $user->only(['nombre', 'correo', 'rol', 'estado']);
        $user->estado = 'inactivo';
        $user->save();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        Auditoria::registrar($request, 'suspender', 'users', $user->id, $antes, $user->only(['estado']));

        return response()->json([
            'success' => true,
            'message' => 'Usuario suspendido y sesiones revocadas',
        ]);
    }

    public function suspender(Request $request, User $user)
    {
        if ($user->rol === 'SuperAdmin') {
            return response()->json(['success' => false, 'message' => 'No se puede suspender a un SuperAdmin'], 403);
        }

        $request->validate(['motivo_suspension' => 'nullable|string|max:250']);

        $antes = $user->only(['estado', 'motivo_suspension']);
        $user->estado = 'inactivo';
        $user->motivo_suspension = $request->input('motivo_suspension');
        $user->save();

        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        Auditoria::registrar($request, 'suspender', 'users', $user->id, $antes, $user->only(['estado', 'motivo_suspension']));

        return response()->json(['success' => true, 'message' => 'Usuario suspendido']);
    }

    public function reactivar(Request $request, User $user)
    {
        $antes = $user->only(['estado', 'motivo_suspension']);
        $user->estado = 'activo';
        $user->motivo_suspension = null;
        $user->save();

        Auditoria::registrar($request, 'reactivar', 'users', $user->id, $antes, $user->only(['estado']));

        return response()->json(['success' => true, 'message' => 'Usuario reactivado']);
    }

    public function sesiones(User $user)
    {
        $sesiones = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'name',
                'ip_address',
                'user_agent',
                'last_used_at',
                'expires_at',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'data' => $sesiones,
        ]);
    }

    public function revocarSesiones(Request $request, User $user)
    {
        $eliminados = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        Auditoria::registrar($request, 'revocar_sesiones', 'personal_access_tokens', $user->id, ['eliminados' => $eliminados], null);

        return response()->json([
            'success' => true,
            'message' => "{$eliminados} sesión(es) revocada(s)",
        ]);
    }

    protected function permisosDeRol($rolId): array
    {
        if (!$rolId) {
            return [];
        }

        return DB::table('rol_has_permisos')
            ->join('secciones', 'secciones.id', '=', 'rol_has_permisos.seccion_id')
            ->where('rol_has_permisos.rol_id', $rolId)
            ->pluck('secciones.nombre')
            ->all();
    }
}
