<?php

namespace App\Http\Controllers;

use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CodigoVerificacionMail;
use App\Models\CodigoVerificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TecnicoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Tecnico::where('tecnicos.estado', 'activo')
        ->join('users', 'tecnicos.user_id', '=', 'users.id')
        ->select('tecnicos.*', 'users.correo as correo')
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
        $correo = User::where('correo', $request->correo)->first();
        if($correo){
            // 4️⃣ Respuesta
            return response()->json([
                'success' => false,
                'message' => 'Correo del tecnico ya registrado.',
                'correo' => $correo,
            ], 500);
        }

            // guardar usuario si no existe
            $usuario = new User();
            $usuario->nombre = $request->nombre;
            $usuario->correo = $request->correo;
            $usuario->contraseña = null;
            $usuario->rol = 'Tecnico';
            $usuario->estado = 'activo';
            $usuario-> save();

            $codigo = Str::random(6);

            CodigoVerificacion::create([
                'correo' => $usuario->correo,
                'codigo' => $codigo,
                'expira_en' => Carbon::now()->addMinutes(240)
            ]);

            // --- Manejo del sello (imagen) ---

            // Reglas recomendadas de validación
            $request->validate([
                'sello' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:5120', // max 5MB
            ]);

            $selloPath = null;
            if ($request->hasFile('sello') && $request->file('sello')->isValid()) {
                $file = $request->file('sello');
                // Nombre seguro y único
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                // Ruta dentro del disco public
                $folder = 'tecnicos';
                // Si no usamos intervention simplemente guardamos
                $path = $file->storeAs($folder, $filename, 'public'); // devuelve 'tecnicos/xxx.jpg'
                $selloPath = $path;
            }

            Mail::to($usuario->correo)->send(new CodigoVerificacionMail($usuario->correo, $codigo));

        return Tecnico::create([
                    'user_id' => $usuario->id,
                    'sello' => $selloPath
                ] + $request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tecnico  $tecnico
     * @return \Illuminate\Http\Response
     */
    public function show(Tecnico $tecnico)
    {
        return Tecnico::findOrFail($id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tecnico  $tecnico
     * @return \Illuminate\Http\Response
     */
    public function edit(Tecnico $tecnico)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tecnico  $tecnico
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tecnico $tecnico)
    {
        // Si ya existe un sello, lo borramos
        if ($request->hasFile('sello') && !empty($tecnico->sello)) {
            Storage::disk('public')->delete($tecnico->sello);
        }

        $usuario = User::where('id', $tecnico->user_id)->first();
        if ($request->correo != $usuario->correo){
            $usuario->correo = $request->correo;
            $usuario-> save();
        }

        $selloPath = $tecnico->sello; // mantener el anterior si no se sube uno nuevo

        // Si viene un archivo válido
        if ($request->hasFile('sello') && $request->file('sello')->isValid()) {
            $file = $request->file('sello');
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $folder = 'tecnicos';
            $path = $file->storeAs($folder, $filename, 'public');
            $selloPath = $path;
        }

        // Actualizamos todos los campos excepto sello
        $data = $request->only(['nombre', 'telefono', 'direccion']);
        $data['sello'] = $selloPath; // aseguramos que se guarde la ruta correcta

        // $tecnico->update($data);
        $tecnico->nombre = $request->nombre;
        $tecnico->telefono = $request->telefono;
        $tecnico->direccion = $request->direccion;
        $tecnico->sello = $selloPath;
        $tecnico->save();

        return $tecnico;

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tecnico  $tecnico
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tecnico $tecnico)
    {
        if (!empty($tecnico->sello)) {
            Storage::disk('public')->delete($tecnico->sello);
        }
        $tecnico->estado = 'inactivo';
        $tecnico->save();
        return $tecnico;
    }
}
