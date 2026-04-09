<?php

namespace App\Http\Controllers;

use App\Models\Recibido_firma;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecibidoFirmaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
            $tokenPlano = $request->bearerToken();
            $hashed = hash('sha256', $tokenPlano);

            $token = DB::table('personal_access_tokens')
                ->where('token', $hashed)
                ->first();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Token inválido'], 403);
            }

            $data = $request->all();
            $ids = [];

            if(!empty($data['recibido']['firma'])) {
                // Decodificar la firma en base64
                $imageData = $data['recibido']['firma'];
                // Remover encabezado "data:image/png;base64,"
                $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
                $imageData = str_replace(' ', '+', $imageData);
                $decoded = base64_decode($imageData);

                // Nombre único
                $filename = 'Firma' . $data['recibido']['reporte_id'] . '.png';
                $folder = 'recibido';
                $path = $folder . '/' . $filename;

                // Guardar en disco public
                Storage::disk('public')->put($path, $decoded);

                // Actualizar el registro
                Recibido_firma::create(array_merge($data['recibido'], [
                    'firma' => $path,
                    'reporte_id' => $data['recibido']['reporte_id']
                ]));

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
     * @param  \App\Models\Recibido_firma  $recibido_firma
     * @return \Illuminate\Http\Response
     */
    public function show(Recibido_firma $recibido_firma)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Recibido_firma  $recibido_firma
     * @return \Illuminate\Http\Response
     */
    public function edit(Recibido_firma $recibido_firma)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recibido_firma  $recibido_firma
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Recibido_firma $recibido_firma)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Recibido_firma  $recibido_firma
     * @return \Illuminate\Http\Response
     */
    public function destroy(Recibido_firma $recibido_firma)
    {
        //
    }
}
