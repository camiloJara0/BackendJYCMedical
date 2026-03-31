<?php

namespace App\Http\Controllers;

use App\Models\Tipo_equipo;
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
        return Tipo_equipo::where('estado', 'activo')->with('sistemas')->get();
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
        // Luego recorres el array de sistemas
        foreach ($request->sistemas as $sistema_id) {
            TipoEquipoSistema::create([
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
        $Tipo_equipo->estado = 'inactivo';
        $Tipo_equipo->save();
        return $Tipo_equipo;
    }
}
