<?php

namespace App\Http\Controllers;

use App\Models\solicitudes_cotizacion;
use App\Models\cotizacion_detalle;
use Illuminate\Support\Facades\Mail;
use App\Mail\cotizacionRecibida;
use Illuminate\Http\Request;

class SolicitudesCotizacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return solicitudes_cotizacion::with('detalles.producto')->get();
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
        // Validación de datos
            $request->validate([
                'nombre' => 'required|string|max:255',
                'correo' => 'nullable|string|email',
                'descripcion' => 'nullable|string',
                'NIT' => 'nullable|integer|min:0',
                'telefono' => 'nullable|numeric|min:1000000000',
                'imagenes_referencia' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120', // max 5MB
                'productos' => 'required|array',
                'productos.*.id' => 'required|integer',
            ]);

            // Guardar solicitud
            $solicitud = solicitudes_cotizacion::create([
                'nombre' => $request->nombre,
                'correo' => $request->correo,
                'descripcion' => $request->descripcion,
                'NIT' => $request->NIT,
                'telefono' => $request->telefono,
                'estado' => 'pendiente',
            ]);

            // Guardar detalles de productos
            foreach ($request->productos as $producto) {
                cotizacion_detalle::create([
                    'solicitud_id' => $solicitud->id,
                    'producto_id' => $producto['id'],
                    'cantidad' => $producto['cantidad'] ?? 1,
                    'comentarios' => $producto['comentarios'] ?? null
                ]);
            }

            // Manejo de imagen de referencia
            $imagenFile = $request->file('imagenes_referencia');


            // Enviar correo con adjunto
            Mail::to('camilojara0000@gmail.com')->send(new cotizacionRecibida($solicitud, $imagenFile));

            return response()->json([
                'success' => true,
                'data' => $solicitud
            ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\solicitudes_cotizacion  $solicitudes_cotizacion
     * @return \Illuminate\Http\Response
     */
    public function show(solicitudes_cotizacion $solicitudes_cotizacion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\solicitudes_cotizacion  $solicitudes_cotizacion
     * @return \Illuminate\Http\Response
     */
    public function edit(solicitudes_cotizacion $solicitudes_cotizacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\solicitudes_cotizacion  $solicitudes_cotizacion
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, solicitudes_cotizacion $solicitudes_cotizacion)
    {
        $solicitudes_cotizacion = solicitudes_cotizacion::where('id', $request->id)->first();

        $solicitudes_cotizacion->fecha_respuesta = now();
        $solicitudes_cotizacion->estado = $request->estado;
        $solicitudes_cotizacion->observaciones_admin = $request->observaciones_admin;

        $solicitudes_cotizacion->save();

        return response()->json([
            'success' => true,
            'data' => $solicitudes_cotizacion->fresh()
        ]);


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\solicitudes_cotizacion  $solicitudes_cotizacion
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, solicitudes_cotizacion $solicitudes_cotizacion)
    {
        // Eliminar los detalles asociados
        $solicitudes_cotizacion->detalles()->delete();

        // Eliminar la cotización
        $solicitudes_cotizacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotización y detalles eliminados correctamente'
        ]);

    }
}
