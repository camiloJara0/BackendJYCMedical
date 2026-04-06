<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Seccion;
use App\Models\Tecnico;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Rol::where('estado', 'activo')->with('permisos')->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // 1️⃣ Crear la nueva profesión
            $rol = new Rol();
            $rol->nombre = $request->nombre;
            $rol->save();

            // 2️⃣ Asociar permisos si vienen en el request
            if (!empty($request->permisos) && is_array($request->permisos)) {
                foreach ($request->permisos as $nombrePermiso) {
                    $permiso = Seccion::where('nombre', $nombrePermiso)->first();

                    if ($permiso) {
                        DB::table('rol_has_permisos')->insert([
                            'rol_id' => $rol->id,
                            'seccion_id' => $permiso->id
                        ]);
                    }
                }
            }

            DB::commit();

            // 3️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'message' => 'Rol creada exitosamente.',
                'data' => $rol
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el rol.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function show(Rol $rol)
    {
        $rol = Rol::findOrFail($id);
        $rol = [];

        $rol = DB::table('rol_has_permisos')
            ->join('secciones', 'rol_has_permisos.seccion_id', '=', 'secciones.id')
            ->where('rol_has_permisos.rol_id', $rol->id)
            ->pluck('secciones.nombre');

        return response()->json([
            'success' => true,
            'data' => $rol
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function edit(Rol $rol)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Rol $rol)
    {
        DB::beginTransaction();

        try {
        // Actualizar los campos
        $rol = Rol::where('id', $request->id)->first();
        if($rol){
            $rol->nombre = $request->nombre;
            $rol->save();
        }

        // 2️⃣ Obtener IDs de permisos desde nombres
        $rolIds = [];
        if (!empty($request->permisos) && is_array($request->permisos)) {
            $rolIds = Seccion::whereIn('nombre', $request->permisos)->pluck('id')->toArray();
        }

        // 3️⃣ Sincronizar permisos (agrega nuevos y elimina los que no están)
        $rol->permisos()->sync($rolIds);


            DB::commit();

            // 3️⃣ Retornar respuesta
            return response()->json([
                'success' => true,
                'message' => 'Profesión actualizada exitosamente.',
                'data' => $rol
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la profesión.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rol  $rol
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rol $rol)
    {
         $rol = Rol::where('id', $request->id)->first();
        if($rol){
            // Desactivar la profesión
            $rol->estado = 0;
            $rol->save();

            // Obtener todos los tecnicos
            $tecnicos = Tecnico::where('rol_id', $rol->id)->get();

            // Desactivar todos los profesionales
            Tecnico::where('rol_id', $rol->id)
                ->update([
                    'estado' => 0,
                ]);

            // Cancelar todas las citas de esos profesionales
            Cita::whereIn('tecnico_id', $tecnicos->pluck('id'))
                ->where('estado', 'inactiva')
                ->update([
                    'estado' => 'cancelada',
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Rol y Tecnicos desactivados exitosamente.'
            ]);

        }
    }
}
