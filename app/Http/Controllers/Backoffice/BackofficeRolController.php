<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Services\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BackofficeRolController extends Controller
{
    public function index()
    {
        $roles = Rol::query()
            ->withCount('permisos')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $roles]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('roles', 'nombre')],
            'permisos' => 'sometimes|array',
            'permisos.*' => 'string|exists:secciones,nombre',
        ]);

        DB::beginTransaction();

        try {
            $rol = Rol::create([
                'nombre' => $datos['nombre'],
                'estado' => 'activo',
            ]);

            if (!empty($datos['permisos'])) {
                $ids = DB::table('secciones')->whereIn('nombre', $datos['permisos'])->pluck('id');
                DB::table('rol_has_permisos')->insert(
                    $ids->map(fn ($id) => ['rol_id' => $rol->id, 'seccion_id' => $id])->all()
                );
            }

            DB::commit();

            Auditoria::registrar($request, 'crear', 'roles', $rol->id, null, $rol->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Rol creado',
                'data' => $rol,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Rol $rol)
    {
        $permisos = DB::table('rol_has_permisos')
            ->join('secciones', 'secciones.id', '=', 'rol_has_permisos.seccion_id')
            ->where('rol_has_permisos.rol_id', $rol->id)
            ->pluck('secciones.nombre')
            ->all();

        $usuarios = DB::table('users')
            ->where('rol_id', $rol->id)
            ->count();

        return response()->json([
            'success' => true,
            'data' => $rol,
            'permisos' => $permisos,
            'usuarios' => $usuarios,
        ]);
    }

    public function update(Request $request, Rol $rol)
    {
        if ($rol->nombre === 'SuperAdmin') {
            return response()->json([
                'success' => false,
                'message' => 'El rol SuperAdmin no se puede modificar por API',
            ], 403);
        }

        $datos = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100', Rule::unique('roles', 'nombre')->ignore($rol->id)],
            'estado' => 'sometimes|in:activo,inactivo',
            'permisos' => 'sometimes|array',
            'permisos.*' => 'string|exists:secciones,nombre',
        ]);

        DB::beginTransaction();

        try {
            $antes = $rol->toArray();
            $rol->fill(array_intersect_key($datos, array_flip(['nombre', 'estado'])))->save();

            if (array_key_exists('permisos', $datos)) {
                $ids = DB::table('secciones')->whereIn('nombre', $datos['permisos'])->pluck('id')->all();
                DB::table('rol_has_permisos')->where('rol_id', $rol->id)->delete();
                DB::table('rol_has_permisos')->insert(
                    array_map(fn ($id) => ['rol_id' => $rol->id, 'seccion_id' => $id], $ids)
                );
            }

            DB::commit();
            Cache::forget("backoffice.access.rol.{$rol->id}");

            Auditoria::registrar($request, 'actualizar', 'roles', $rol->id, $antes, $rol->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado',
                'data' => $rol,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, Rol $rol)
    {
        if ($rol->nombre === 'SuperAdmin') {
            return response()->json([
                'success' => false,
                'message' => 'El rol SuperAdmin no se puede eliminar',
            ], 403);
        }

        $enUso = DB::table('users')->where('rol_id', $rol->id)->exists();

        if ($enUso) {
            return response()->json([
                'success' => false,
                'message' => 'El rol tiene usuarios asignados',
            ], 409);
        }

        $antes = $rol->toArray();

        DB::table('rol_has_permisos')->where('rol_id', $rol->id)->delete();
        $rol->delete();

        Cache::forget("backoffice.access.rol.{$rol->id}");

        Auditoria::registrar($request, 'eliminar', 'roles', $rol->id, $antes, null);

        return response()->json(['success' => true, 'message' => 'Rol eliminado']);
    }

    public function permisos(Rol $rol)
    {
        $asignados = DB::table('rol_has_permisos')
            ->where('rol_id', $rol->id)
            ->pluck('seccion_id')
            ->all();

        $disponibles = DB::table('secciones')->orderBy('nombre')->get(['id', 'nombre']);

        return response()->json([
            'success' => true,
            'asignados' => $asignados,
            'disponibles' => $disponibles,
        ]);
    }

    public function sincronizarPermisos(Request $request, Rol $rol)
    {
        if ($rol->nombre === 'SuperAdmin') {
            return response()->json([
                'success' => false,
                'message' => 'El rol SuperAdmin no se puede modificar por API',
            ], 403);
        }

        $datos = $request->validate([
            'permisos' => 'required|array',
            'permisos.*' => 'integer|exists:secciones,id',
        ]);

        $antes = DB::table('rol_has_permisos')->where('rol_id', $rol->id)->pluck('seccion_id')->all();

        DB::table('rol_has_permisos')->where('rol_id', $rol->id)->delete();
        DB::table('rol_has_permisos')->insert(
            array_map(fn ($id) => ['rol_id' => $rol->id, 'seccion_id' => $id], $datos['permisos'])
        );

        Cache::forget("backoffice.access.rol.{$rol->id}");

        Auditoria::registrar($request, 'actualizar_permisos', 'rol_has_permisos', $rol->id, $antes, $datos['permisos']);

        return response()->json([
            'success' => true,
            'message' => 'Permisos sincronizados',
        ]);
    }

    public function secciones()
    {
        $secciones = DB::table('secciones')->orderBy('nombre')->get(['id', 'nombre']);

        return response()->json(['success' => true, 'data' => $secciones]);
    }
}
