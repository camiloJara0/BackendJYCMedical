<?php

namespace App\Http\Controllers;

use App\Models\Reporte;
use App\Models\ReporteImagen;
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
use App\Models\Resultado_reporte;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\FirmarReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReporteController extends Controller
{
    /** Formatos de imagen permitidos (mime real verificado con getimagesizefromstring). */
    private const IMAGEN_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Tamaño máximo por imagen en bytes (5 MB). */
    private const IMAGEN_MAX_BYTES = 5242880;

    /** Cantidad máxima de imágenes por reporte. */
    private const IMAGEN_MAX_CANTIDAD = 20;

    /** Lado máximo tras la optimización (px). */
    private const IMAGEN_MAX_PX = 1600;

    /** Calidad JPEG de recompresión. */
    private const IMAGEN_CALIDAD = 80;

    /** Extensiones permitidas para la validación de data URIs. */
    private const IMAGEN_EXTENSIONES = ['jpeg', 'jpg', 'png', 'webp'];

    /** Tamaños de visualización válidos en el PDF. */
    private const TAMANOS_PDF = ['100', '50', '30'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente', 'tecnico', 'cliente', 'equipo', 'firmaRecibido', 'historialEstadosReporte', 'resultadoReporte', 'images')->
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

            $reporte = Reporte::create($this->datosReporte($data));
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

                Resultado_reporte::create([
                    'reporte_id' => $reporte->id,
                    'estado' => $data['resultado']['estado'] ?? null,
                    'observacion' => $data['resultado']['observacion'] ?? null
                ]);

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

            $this->syncimagenes($reporte, $request);

            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
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

            if (array_key_exists('recomendaciones', $data) && is_array($data['recomendaciones'])) {
                $rec = $data['recomendaciones'];
                $reporte->fill([
                    'recomendaciones_cliente' => $rec['cliente'] ?? null,
                    'riesgos_identificados' => $rec['riesgos'] ?? null,
                    'proximas_acciones' => $rec['proximas'] ?? null,
                ]);
                $reporte->save();
            }

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

                Resultado_reporte::create([
                    'reporte_id' => $reporte->id,
                    'estado' => $data['resultado']['estado'] ?? null,
                    'observacion' => $data['resultado']['observacion'] ?? null
                ]);

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

            $this->syncimagenes($reporte, $request);

            DB::commit();

            return response()->json([
                'success' => true, 
                'ids' => $ids,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
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
            $reporte = Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente.sistema', 'tecnico', 'cliente', 'equipo', 'firmaRecibido', 'images')->findOrFail($id);

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



        $reporte = Reporte::with('actividades', 'materiales', 'mediciones', 'repuestos', 'accesorios', 'estado_componente.componente.sistema', 'tecnico', 'cliente', 'equipo', 'firmaRecibido', 'images')->findOrFail($request->id);
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

    /**
     * Combina los datos del reporte con las recomendaciones del formulario.
     */
    private function datosReporte(array $data): array
    {
        $reporte = $data['reporte'] ?? [];
        $rec = $data['recomendaciones'] ?? [];

        return array_merge($reporte, [
            'recomendaciones_cliente' => $rec['cliente'] ?? ($reporte['recomendaciones_cliente'] ?? null),
            'riesgos_identificados' => $rec['riesgos'] ?? ($reporte['riesgos_identificados'] ?? null),
            'proximas_acciones' => $rec['proximas'] ?? ($reporte['proximas_acciones'] ?? null),
        ]);
    }

    /**
     * Sincroniza las imágenes del reporte: conserva las existentes (actualizando
     * descripción, tamaño y posición), crea las nuevas desde base64 y elimina
     * las filas y archivos físicos que ya no se usan.
     *
     * @throws ValidationException si alguna imagen no cumple formato, tamaño o cantidad.
     */
    private function syncimagenes(Reporte $reporte, Request $request): void
    {
        // Sin campo imágenes en el payload: no tocar el conjunto actual.
        if (!$request->has('images')) {
            return;
        }

        $items = $this->normalizarImagenes((array) $request->input('images', []));

        // Compatibilidad con el contrato anterior (URLs planas de imágenes existentes).
        foreach ((array) $request->input('existing_image_urls', []) as $url) {
            if (!empty($url) && is_string($url)) {
                $items[] = ['url' => $url];
            }
        }

        $this->validarImagenes($items);

        $existentes = $reporte->images()->get()->keyBy('id');
        $existentesPorUrl = $existentes->keyBy('url');
        $conservadas = [];
        $position = 0;

        foreach ($items as $item) {
            $descripcion = isset($item['descripcion']) ? trim((string) $item['descripcion']) : null;
            $tamano = $this->normalizarTamano($item['tamano'] ?? null);
            $creada = null;

            if (!empty($item['id']) && $existentes->has($item['id'])) {
                $creada = $existentes[$item['id']];
                $creada->fill([
                    'position' => $position,
                    'descripcion' => $descripcion,
                    'tamaño' => $tamano,
                ])->save();
            } elseif (!empty($item['base64'])) {
                $procesada = $this->procesarImagenBase64($item['base64']);
                $ruta = 'reporte/images/' . Str::uuid() . '.' . $procesada['extension'];
                Storage::disk('public')->put($ruta, $procesada['bytes']);

                $creada = $reporte->images()->create([
                    'url' => asset('storage/' . $ruta),
                    'position' => $position,
                    'descripcion' => $descripcion,
                    'tamaño' => $tamano,
                ]);
            } elseif (!empty($item['url'])) {
                $creada = $existentesPorUrl[$item['url']] ?? null;

                if ($creada) {
                    $creada->fill([
                        'position' => $position,
                        'descripcion' => $descripcion,
                        'tamaño' => $tamano,
                    ])->save();
                } else {
                    $creada = $reporte->images()->create([
                        'url' => $item['url'],
                        'position' => $position,
                        'descripcion' => $descripcion,
                        'tamaño' => $tamano,
                    ]);
                }
            } else {
                continue;
            }

            $conservadas[] = $creada->id;
            $position++;
        }

        $aEliminar = $reporte->images()->whereNotIn('id', $conservadas)->get();

        foreach ($aEliminar as $imagen) {
            $this->eliminarArchivoImagen($imagen);
            $imagen->delete();
        }
    }

    /**
     * Normaliza el payload de imágenes: acepta objetos estructurados
     * { id?, url?, base64?, descripcion?, tamano? } y strings planos
     * (data URI o URL) heredados del contrato anterior.
     */
    private function normalizarImagenes(array $items): array
    {
        $normalizadas = [];

        foreach ($items as $item) {
            if (is_string($item) && !empty($item)) {
                $normalizadas[] = str_starts_with($item, 'data:image')
                    ? ['base64' => $item]
                    : ['url' => $item];
            } elseif (is_array($item)) {
                $normalizadas[] = [
                    'id' => $item['id'] ?? null,
                    'url' => $item['url'] ?? null,
                    'base64' => $item['base64'] ?? null,
                    'descripcion' => $item['descripcion'] ?? null,
                    'tamano' => $item['tamano'] ?? ($item['tamaño'] ?? null),
                ];
            }
        }

        return $normalizadas;
    }

    /**
     * Valida cantidad, formato real (sniffing MIME) y tamaño de cada imagen nueva.
     *
     * @throws ValidationException
     */
    private function validarImagenes(array $items): void
    {
        if (count($items) > self::IMAGEN_MAX_CANTIDAD) {
            throw ValidationException::withMessages([
                'images' => 'Máximo ' . self::IMAGEN_MAX_CANTIDAD . ' imágenes por reporte.',
            ]);
        }

        foreach ($items as $index => $item) {
            $etiqueta = 'La imagen ' . ($index + 1);

            if (empty($item['base64'])) {
                continue; // Las URLs existentes no se revalidan.
            }

            if (!preg_match('#^data:image/(' . implode('|', self::IMAGEN_EXTENSIONES) . ');base64,#i', $item['base64'])) {
                throw ValidationException::withMessages([
                    'images' => $etiqueta . ' debe estar en formato JPG, PNG o WEBP.',
                ]);
            }

            $bytes = base64_decode(substr($item['base64'], strpos($item['base64'], ',') + 1), true);

            if ($bytes === false || $bytes === '') {
                throw ValidationException::withMessages([
                    'images' => $etiqueta . ' no es un base64 válido.',
                ]);
            }

            if (strlen($bytes) > self::IMAGEN_MAX_BYTES) {
                throw ValidationException::withMessages([
                    'images' => $etiqueta . ' supera el tamaño máximo de 5 MB.',
                ]);
            }

            $info = @getimagesizefromstring($bytes);

            if ($info === false || !in_array($info['mime'], self::IMAGEN_MIMES, true)) {
                throw ValidationException::withMessages([
                    'images' => $etiqueta . ' no es una imagen válida. Formatos permitidos: JPG, PNG, WEBP.',
                ]);
            }
        }
    }

    /**
     * Decodifica, valida y optimiza una imagen en base64.
     *
     * @return array{bytes: string, extension: string}
     * @throws ValidationException
     */
    private function procesarImagenBase64(string $dataUri): array
    {
        preg_match('#^data:image/([a-z0-9]+);base64,#i', $dataUri, $matches);
        $extensionOriginal = strtolower($matches[1] ?? 'jpeg');
        if ($extensionOriginal === 'jpg') {
            $extensionOriginal = 'jpeg';
        }

        $bytes = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);

        if ($bytes === false || $bytes === '') {
            throw ValidationException::withMessages(['images' => 'Una de las imágenes no es un base64 válido.']);
        }

        if (strlen($bytes) > self::IMAGEN_MAX_BYTES) {
            throw ValidationException::withMessages(['images' => 'Una de las imágenes supera el tamaño máximo de 5 MB.']);
        }

        $info = @getimagesizefromstring($bytes);

        if ($info === false || !in_array($info['mime'], self::IMAGEN_MIMES, true)) {
            throw ValidationException::withMessages([
                'images' => 'Formato de imagen no permitido. Usa JPG, PNG o WEBP.',
            ]);
        }

        $optimizada = $this->optimizarImagen($bytes, $info['mime']);

        return [
            'bytes' => $optimizada['bytes'],
            'extension' => $optimizada['extension'],
        ];
    }

    /**
     * Reduce la imagen a un máximo de IMAGEN_MAX_PX y recomprime en JPEG
     * (calidad IMAGEN_CALIDAD) para disminuir el peso en disco y en el PDF.
     * Si GD no está disponible conserva los bytes originales.
     */
    private function optimizarImagen(string $bytes, string $mime): array
    {
        $extensionOriginal = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpeg');

        if (!function_exists('imagecreatefromstring')) {
            return ['bytes' => $bytes, 'extension' => $extensionOriginal];
        }

        $origen = @imagecreatefromstring($bytes);

        if (!$origen) {
            return ['bytes' => $bytes, 'extension' => $extensionOriginal];
        }

        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $factor = min(1, self::IMAGEN_MAX_PX / max($ancho, $alto));
        $nuevoAncho = max(1, (int) round($ancho * $factor));
        $nuevoAlto = max(1, (int) round($alto * $factor));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        // Fondo blanco para conservar el aspecto si la imagen tiene transparencia.
        $blanco = imagecolorallocate($destino, 255, 255, 255);
        imagefill($destino, 0, 0, $blanco);
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

        ob_start();
        imagejpeg($destino, null, self::IMAGEN_CALIDAD);
        $optimizada = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($destino);

        if (empty($optimizada)) {
            return ['bytes' => $bytes, 'extension' => $extensionOriginal];
        }

        return ['bytes' => $optimizada, 'extension' => 'jpg'];
    }

    /**
     * Normaliza el tamaño de visualización en el PDF.
     */
    private function normalizarTamano(?string $tamano): string
    {
        $valor = $tamano !== null ? trim((string) $tamano) : '';

        return in_array($valor, self::TAMANOS_PDF, true) ? $valor : '50';
    }

    /**
     * Elimina el archivo físico de una imagen solo si vive en la carpeta
     * gestionada por este controlador (reporte/images/).
     */
    private function eliminarArchivoImagen(ReporteImagen $imagen): void
    {
        $ruta = $this->rutaRelativaDesdeUrl($imagen->url);

        if ($ruta && str_starts_with($ruta, 'reporte/images/')) {
            Storage::disk('public')->delete($ruta);
        }
    }

    /**
     * Convierte una URL de storage (absoluta o relativa) en ruta relativa al disco public.
     */
    private function rutaRelativaDesdeUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return null;
        }

        $marca = '/storage/';
        $pos = strpos($path, $marca);

        return $pos === false
            ? ltrim($path, '/')
            : substr($path, $pos + strlen($marca));
    }
}
