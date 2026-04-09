<?php

namespace App\Http\Controllers;

use App\Models\Tipo_equipo;
use App\Models\Tipo_equipo_sistema;
use App\Models\Equipo;
use App\Models\Cita;
use Illuminate\Http\Request;

class TipoEquipoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Tipo_equipo::with('sistemas')->get();
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
        $tipo_equipo = Tipo_equipo::create($request->all());
        // array de sistemas
        foreach ($request->sistemas as $sistema_id) {
            Tipo_equipo_sistema::create([
                'sistema_id'     => $sistema_id,
                'tipo_equipo_id' => $tipo_equipo->id,
            ]);
        }

        return $tipo_equipo;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function show(Tipo_equipo $tipo_equipo)
    {
        return $tipo_equipo->load('sistemas.componentes');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function edit(Tipo_equipo $tipo_equipo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tipo_equipo $tipo_equipo)
    {
        // Actualizas los datos del tipo de equipo
        $tipo_equipo->update($request->all());

        // Sincronizas los sistemas asociados
        $tipo_equipo->sistemas()->sync($request->sistemas);

        return $tipo_equipo;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tipo_equipo  $tipo_equipo
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tipo_equipo $tipo_equipo)
    {
        // Obtener todos los equipos asociados al tipo de equipo
        $equipos = Equipo::where('tipo_equipo_id', $tipo_equipo->id)->get();

        foreach ($equipos as $equipo) {
            // Marcar el equipo como inactivo
            $equipo->update(['estado' => 'inactivo']);

            // Rechazar todas las citas asociadas a este equipo
            Cita::where('equipo_id', $equipo->id)
                ->update(['estado' => 'rechazada']);
        }

        $tipo_equipo->estado = 'inactivo';
        $tipo_equipo->save();
        return $tipo_equipo;
    }
}
