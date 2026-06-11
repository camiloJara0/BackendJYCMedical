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
        $citas = Cita::with(['tecnico', 'cliente', 'equipo', 'ultimo_estado', 'equipos'])
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
        $equipoIds = $request->equipo_id ?? [];
        
        $cita = Cita::create([
            'estado' => 'inactiva',
            'tecnico_id' => $request->tecnico_id,
            'cliente_id' => $request->cliente_id,
            'tipo' => $request->tipo,
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'equipo_id' => count($equipoIds) === 1 ? $equipoIds[0] : null,
        ]);

        // Si equipo_id es un array, crear registros en la tabla pivote
        if (count($equipoIds) > 1) {
            foreach ($equipoIds as $equipoId) {
                $cita->equipos()->attach($equipoId, [
                    'estado' => 'pendiente',
                    'observacion' => null,
                ]);
            }
        }

        return $cita;
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

        $equipoIds = $request->equipo_id ?? [];
        $cita->update(array_merge(
                    $request->all(),
                    ['equipo_id' => count($equipoIds) === 1 ? $equipoIds[0] : null]
                ));

        if(count($equipoIds) > 1) {
            $cita->equipos()->sync($equipoIds);
        }

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
