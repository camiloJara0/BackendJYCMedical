<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\Actividad;
use App\Models\Material;
use App\Models\Medicion;
use App\Models\Repuesto;
use App\Models\Accesorio;
use App\Models\Tecnico;
use App\Models\Estado_componente;
use App\Models\Recibido_firma;
use App\Models\Cita;
use App\Models\Historial_estados_cita;
use App\Models\Historial_estados_reporte;
use App\Models\Cita_equipo;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\FirmarReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente', 'tecnico', 'cliente', 'equipo', 'firmaRecibido', 'historialEstadosReporte')->
        orderBy('fecha', 'desc')->orderBy('id', 'desc')
        ->get();
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


            $cita = Cita::where('id', $data['cita']['id'])->first();
            if($cita->estado == 'realizada'){
                return response()->json([
                    'success' => true, 
                    'message' => 'Cita ya realizada, espera que se recargue los estados',
                ], 500);
            }

            $reporte = Reporte::create($data['reporte']);
            $ids['Reporte'] = $reporte;


            $actividad = Actividad::create([
                'descripcion' => $data['actividades'] ?? 'PENDIENTE',
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

            $ids['Accesorios'] = [];
            foreach ($data['accesorios'] ?? [] as $accesorio) {
                $nuevo = Accesorio::create([...$accesorio, 'reporte_id' => $reporte->id]);
                $ids['Accesorios'][] = $nuevo->id;
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
                if($cita->equipo_id !== null){
                    Historial_estados_cita::create([
                        'cita_id' => $data['cita']['id'],
                        'tecnico_id' => $reporte->tecnico_id ?? null,
                        'nombre_estado' => 'realizada',
                        'observaciones' => 'Reporte generado con ID: ' . $reporte->id
                    ]);
                    $cita->update([
                            'estado' => 'realizada',
                        ]);
                } else {
                    $cita_equipo = Cita_equipo::where('equipo_id', $data['equipo']['id'])
                        ->where('cita_id', $cita->id)
                        ->update([
                            'estado' => 'realizada',
                            'observacion' => 'Reporte generado con ID: ' . $reporte->id
                        ]);

                    // validacion si todos los equipos de la cita están realizados, entonces actualizar la cita a realizada
                    $totalEquipos = Cita_equipo::where('cita_id', $cita->id)->count();
                    $equiposRealizados = Cita_equipo::where('cita_id', $cita->id)->where('estado', 'realizada')->count();

                    if ($totalEquipos === $equiposRealizados) {
                        $cita->update(['estado' => 'realizada']);
                    }
                }
            }

            // Solo procesar firma y correo si el estado es 'realizada'
            if ($data['reporte']['estado'] == 'realizada') {
                if (!empty($data['recibido']['firma'])) {
                    // Decodificar la firma en base64
                    $imageData = $data['recibido']['firma'];
                    // Remover encabezado "data:image/png;base64,"
                    $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
                    $imageData = str_replace(' ', '+', $imageData);
                    $decoded = base64_decode($imageData);

                    // Nombre único
                    $filename = 'Firma' . $reporte->id . '.png';
                    $folder = 'recibido';
                    $path = $folder . '/' . $filename;

                    // Guardar en disco public
                    Storage::disk('public')->put($path, $decoded);

                    // Actualizar el registro
                    Recibido_firma::create(array_merge($data['recibido'], [
                        'firma' => $path,
                        'reporte_id' => $reporte->id
                    ]));
                } else {
                    // Enviar correo si no hay firma
                    // Aquí separas los correos por coma
                    $correos = explode(',', $data['recibido']['correo']);

                    // Limpias espacios en blanco
                    $correos = array_map('trim', $correos);

                    // Envías a cada correo
                    foreach ($correos as $correo) {
                        if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                            $token = Str::random(64);

                            DB::table('personal_access_tokens')->insert([
                                'tokenable_type' => Recibido_firma::class,
                                'tokenable_id'   => $reporte->id, // registro relacionado
                                'name'           => 'firma_recibido',
                                'token'          => hash('sha256', $token), // se guarda hasheado
                                'abilities'      => json_encode(['sign']),
                                'expires_at'     => now()->addDays(7), // opcional: expira en 7 días
                            ]);

                            // Enviar por correo el enlace con el token plano
                            $url = "/FirmarReporte?token={$token}";

                            // Enviar correo individual
                            Mail::to($correo)->send(new FirmarReporte($reporte, $url));
                        }
                    }

                    $data['reporte']['estado'] = 'En Revisión';
                }
            }

            if (!empty($data['reporte']['estado'])) {
                $reporte->estado = $data['reporte']['estado'];
                $reporte->save();

                Historial_estados_reporte::create([
                    'reporte_id' => $reporte->id,
                    'tecnico_id' => $reporte->tecnico_id ?? null,
                    'nombre_estado' => $data['reporte']['estado'],
                    'observaciones' => $data['estado']['observacion']
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
        DB::beginTransaction();

        try {
            $data = $request->all();
            $ids = [];

            $actividad = Actividad::where('reporte_id', $reporte->id)->first();
            $actividad->update([
                'descripcion' => $data['actividades'],
            ]);
            $ids['Actividad'] = $actividad->id;

            $ids['Materiales'] = [];
            foreach ($data['materiales'] ?? [] as $material) {
                $nuevo = Material::updateOrCreate(
                    ['id' => $material['id'] ?? null], // condición de búsqueda
                    [...$material, 'reporte_id' => $reporte->id] // datos a actualizar/crear
                );
                $ids['Materiales'][] = $nuevo->id;
            }

            $ids['Mediciones'] = [];
            foreach ($data['mediciones'] ?? [] as $medicion) {
                $nuevo = Medicion::updateOrCreate(
                    ['id' => $medicion['id'] ?? null],
                    [...$medicion, 'reporte_id' => $reporte->id]
                );
                $ids['Mediciones'][] = $nuevo->id;
            }

            $ids['Accesorios'] = [];
            foreach ($data['accesorios'] ?? [] as $accesorio) {
                $nuevo = Accesorio::updateOrCreate(
                    ['id' => $accesorio['id'] ?? null],
                    [...$accesorio, 'reporte_id' => $reporte->id]
                );
                $ids['Accesorios'][] = $nuevo->id;
            }

            $ids['Repuestos'] = [];
            foreach ($data['repuestos'] ?? [] as $repuesto) {
                $nuevo = Repuesto::updateOrCreate(
                    ['id' => $repuesto['id'] ?? null],
                    [...$repuesto, 'reporte_id' => $reporte->id]
                );
                $ids['Repuestos'][] = $nuevo->id;
            }


            $componentes = $data['componentes'] ?? [];
            // IDs que vienen en la request
            $idsComponentes = collect($componentes)->pluck('componente_id')->toArray();
            // Eliminar los que ya no vienen
            Estado_componente::where('reporte_id', $reporte->id)
                ->whereNotIn('componente_id', $idsComponentes)
                ->delete();

            // Crear o actualizar
            foreach ($componentes as $componente) {
                Estado_componente::updateOrCreate(
                    [
                        'reporte_id' => $reporte->id,
                        'componente_id' => $componente['componente_id']
                    ],
                    [
                        ...$componente
                    ]
                );
            }

            if (!empty($data['reporte']['estado'])) {
                
                Historial_estados_reporte::create([
                    'reporte_id' => $reporte->id,
                    'tecnico_id' => $reporte->tecnico_id ?? null,
                    'nombre_estado' => $data['reporte']['estado'],
                    'observaciones' => $data['estado']['observacion']
                    ]);
                $reporte->estado = $data['reporte']['estado'];
                $reporte->save();
            }

            // Solo procesar firma y correo si el estado es 'realizada'
            if ($data['reporte']['estado'] == 'realizada') {
                if (!empty($data['recibido']['firma'])) {
                    // Decodificar la firma en base64
                    $imageData = $data['recibido']['firma'];
                    // Remover encabezado "data:image/png;base64,"
                    $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
                    $imageData = str_replace(' ', '+', $imageData);
                    $decoded = base64_decode($imageData);

                    // Nombre único
                    $filename = 'Firma' . $reporte->id . '.png';
                    $folder = 'recibido';
                    $path = $folder . '/' . $filename;

                    // Guardar en disco public
                    Storage::disk('public')->put($path, $decoded);

                    // Actualizar el registro
                    $recibido = Recibido_firma::where('reporte_id', $reporte->id)->first();
                    if($recibido){
                        $recibido->update(array_merge($data['recibido'], [
                            'firma' => $path,
                        ]));
                    } else {
                        Recibido_firma::create(array_merge($data['recibido'], [
                            'firma' => $path,
                            'reporte_id' => $reporte->id
                        ]));
                    }
                } else {
                    // Enviar correo si no hay firma
                    $token = Str::random(64);

                    DB::table('personal_access_tokens')->insert([
                        'tokenable_type' => Recibido_firma::class,
                        'tokenable_id'   => $reporte->id, // registro relacionado
                        'name'           => 'firma_recibido',
                        'token'          => hash('sha256', $token), // se guarda hasheado
                        'abilities'      => json_encode(['sign']),
                        'expires_at'     => now()->addDays(7), // opcional: expira en 7 días
                    ]);

                    // Enviar por correo el enlace con el token plano
                    $url = "/FirmarReporte?token={$token}";

                    Mail::to($data['recibido']['correo'])->send(new FirmarReporte($reporte, $url));

                    $reporte->estado = 'En Revisión';
                    $reporte->save();
                }
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reporte  $reporte
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reporte $reporte)
    {
        $reporte->estado = 'eliminada';
        $reporte->save();
        return $reporte;
    }

    public function imprimir($id)
    {
        try {
            $reporte = Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente.sistema', 'tecnico', 'cliente', 'equipo', 'firmaRecibido')->findOrFail($id);

            $totalPages = 1;
            $fileName = 'reporte_' . $reporte->id . '_' . $reporte->equipo->nombre . '.pdf';

            $pdfTemp = \PDF::loadView('pdf.reporte', compact('reporte', 'totalPages'));
            $pdfTemp->render();
            $totalPages = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

            $pdf = \PDF::loadView('pdf.reporte', [
                'reporte' => $reporte,
                'totalPages' => $totalPages
            ]);

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Expose-Headers', 'Content-Disposition')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            Log::error('Error generando PDF: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'No se pudo generar el PDF',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function imprimirConTokenEspecial(Request $request)
    {
        $tokenPlano = $request->bearerToken();
        $hashed = hash('sha256', $tokenPlano);

        $token = DB::table('personal_access_tokens')
            ->where('token', $hashed)
            ->first();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token inválido'], 403);
        }



        $reporte = Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente.sistema', 'tecnico', 'cliente', 'equipo', 'firmaRecibido')->findOrFail($request->id);
        $totalPages = 1;
        $fileName = 'reporte_' . $reporte->id . '_' . $reporte->equipo->nombre . '.pdf';

        $pdfTemp = \PDF::loadView('pdf.reporte', compact('reporte', 'totalPages'));
        $pdfTemp->render();
        $totalPages = $pdfTemp->getDomPDF()->getCanvas()->get_page_count();

        $pdf = \PDF::loadView('pdf.reporte', [
            'reporte' => $reporte,
            'totalPages' => $totalPages
        ]);
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Expose-Headers', 'Content-Disposition')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
