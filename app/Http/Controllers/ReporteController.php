<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Actividad;
use App\Models\Material;
use App\Models\Medicion;
use App\Models\Repuesto;
use App\Models\Estado_componente;
use App\Models\Cita;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'estado_componente.componente', 'cita', 'tecnico', 'cliente', 'equipo')->get();
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
            $data = $request->all();
            $ids = [];


            $reporte = Reporte::create($data['reporte']);
            $ids['Reporte'] = $reporte;

            $actividad = Actividad::create([
                'descripcion' => $data['actividades'],
                'reporte_id'  => $reporte->id
            ]);
            $ids['Actividad'] = $actividad->id;

            $ids['Materiales'] = [];
            foreach ($data['materiales'] ?? [] as $material) {
                $nuevo = Material::create([...$material, 'reporte_id' => $reporte->id]);
                $ids['Materiales'][] = $nuevo->id;
            }

            $ids['Mediciones'] = [];
            foreach ($data['mediciones'] ?? [] as $medicion) {
                $nuevo = Medicion::create([...$medicion, 'reporte_id' => $reporte->id]);
                $ids['Mediciones'][] = $nuevo->id;
            }

            $ids['Repuestos'] = [];
            foreach ($data['repuestos'] ?? [] as $repuesto) {
                $nuevo = Repuesto::create([...$repuesto, 'reporte_id' => $reporte->id]);
                $ids['Repuestos'][] = $nuevo->id;
            }

            $ids['EstadoComponentes'] = [];
            foreach ($data['componentes'] ?? [] as $componente) {
                $nuevo = Estado_componente::create([...$componente, 'reporte_id' => $reporte->id]);
                $ids['EstadoComponentes'][] = $nuevo->id;
            }

            // Actualizar estado de la Cita
            if (!empty($data['cita'])) {
                Cita::where('id', $data['cita']['id'] ?? null)
                    ->update([
                        'estado' => 'realizada',
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar Reporte', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reporte  $reporte
     * @return \Illuminate\Http\Response
     */
    public function show(Reporte $reporte)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reporte  $reporte
     * @return \Illuminate\Http\Response
     */
    public function edit(Reporte $reporte)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reporte  $reporte
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reporte $reporte)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reporte  $reporte
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reporte $reporte)
    {
        //
    }

    public function imprimir($id)
    {
        $reporte = Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'estado_componente.componente.sistema', 'tecnico', 'cliente', 'equipo')->findOrFail($id);
        
        $fileName = 'reporte_' . $reporte->id . '_' . $reporte->equipo->nombre . '.pdf';
        $pdf = \PDF::loadView('pdf.reporte', compact('reporte'));
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
