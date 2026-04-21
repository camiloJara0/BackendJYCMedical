<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Historial_estados_cita;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CitaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $citas = DB::table('citas')
        ->join('equipos', 'citas.equipo_id', '=', 'equipos.id')
        ->join('tecnicos', 'citas.tecnico_id', '=', 'tecnicos.id')
        ->join('clientes', 'citas.cliente_id', '=', 'clientes.id')
        ->select(
            'citas.*',
            'equipos.nombre as nombre_equipo',
            'tecnicos.nombre as nombre_tecnico',
            'clientes.nombre as nombre_cliente',
        DB::raw('(SELECT nombre_estado 
                  FROM historial_estados_citas 
                  WHERE historial_estados_citas.cita_id = citas.id 
                  ORDER BY historial_estados_citas.created_at DESC 
                  LIMIT 1) as ultimo_estado'),
        DB::raw('(SELECT observaciones 
                  FROM historial_estados_citas 
                  WHERE historial_estados_citas.cita_id = citas.id 
                  ORDER BY historial_estados_citas.created_at DESC 
                  LIMIT 1) as ultima_observacion')

        )
        ->orderBy('fecha', 'desc')
        ->get();

        return $citas;
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
        return Cita::create([
                    'estado' => 'inactiva',
                ] + $request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function show(Cita $cita)
    {
        return Cita::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function edit(Cita $cita)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cita $cita)
    {
        $cita = Cita::where('id', $request->id)->first();
        Historial_estados_cita::create([
            'cita_id' => $cita->id,
            'tecnico_id' => $request->tecnico_id,
            'nombre_estado' => 'editada',
            'observaciones' => $request->motivo_edicion,
        ]);
        $cita->update($request->all());
        return $cita;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Cita $cita)
    {   
        $cita = Cita::where('id', $request->id)->first();
        

        Historial_estados_cita::create([
            'cita_id' => $cita->id,
            'tecnico_id' => $request->tecnico_id,
            'nombre_estado' => 'cancelada',
            'observaciones' => $request->observaciones,
        ]);

        $cita->update(['estado' => 'cancelada']);
        return $cita;

    }

    public function cancelar(Request $request)
    {

        $cita = Cita::where('id', $request->id)->first();

        Historial_estados_cita::create([
            'cita_id' => $cita->id,
            'tecnico_id' => $request->tecnico_id,
            'nombre_estado' => 'cancelada',
            'observaciones' => $request->motivo_cancelacion,
        ]);

        $cita->update(['estado' => 'cancelada']);
        return $cita;

    }
}
