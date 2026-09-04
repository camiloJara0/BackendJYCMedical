<?php

namespace App\Http\Controllers;

use App\Models\SolicitudCita;
use App\Models\Cliente;
use App\Models\Equipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\SolicitudCitaRecibida;

class SolicitudCitaController extends Controller
{
    public function index()
    {
        $solicitudes = SolicitudCita::with(['cliente', 'equipo'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($solicitudes);
    }

    public function store(Request $request)
    {
        // Validar Turnstile
        $turnstileToken = $request->input('turnstile_token');
        if ($turnstileToken) {
            $secretKey = '0x4AAAAAAAFpSsnN-kX4Y0N'; // Secret key de Cloudflare Turnstile
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secretKey,
                'response' => $turnstileToken,
                'remoteip' => $request->ip(),
            ]);
            $result = $response->json();
            if (!$result['success']) {
                return response()->json(['success' => false, 'message' => 'Verificación CAPTCHA fallida.'], 422);
            }
        }

        $request->validate([
            'nombre_contacto' => 'required|string|max:255',
            'correo' => 'nullable|email',
            'telefono' => 'nullable|string',
            'NIT' => 'nullable|string',
            'razon_social' => 'nullable|string',
            'serial_equipo' => 'nullable|string',
            'marca' => 'nullable|string',
            'modelo' => 'nullable|string',
            'tipo_equipo_descripcion' => 'nullable|string',
            'tipo_cita' => 'required|in:mantenimiento,revision,reparacion,otro',
            'motivo' => 'nullable|string',
        ]);

        $cliente_id = null;
        $equipo_id = null;

        // Buscar cliente por NIT
        if ($request->NIT) {
            $cliente = Cliente::where('NIT', $request->NIT)->first();
            if ($cliente) {
                $cliente_id = $cliente->id;
            }
        }

        // Buscar equipo por serial
        if ($request->serial_equipo) {
            $equipo = Equipo::where('serie', $request->serial_equipo)->first();
            if ($equipo) {
                $equipo_id = $equipo->id;
            }
        }

        $solicitud = SolicitudCita::create([
            'NIT' => $request->NIT,
            'razon_social' => $request->razon_social,
            'nombre_contacto' => $request->nombre_contacto,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'serial_equipo' => $request->serial_equipo,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'tipo_equipo_descripcion' => $request->tipo_equipo_descripcion,
            'tipo_cita' => $request->tipo_cita,
            'motivo' => $request->motivo,
            'estado' => 'pendiente',
            'cliente_id' => $cliente_id,
            'equipo_id' => $equipo_id,
        ]);

        // Enviar correo al administrador
        try {
            Mail::to('camilojara0000@gmail.com')->send(new SolicitudCitaRecibida($solicitud));
        } catch (\Exception $e) {
            // No falla la petición si el correo no se envía
        }

        return response()->json([
            'success' => true,
            'data' => $solicitud
        ]);
    }

    public function show($id)
    {
        $solicitud = SolicitudCita::with(['cliente', 'equipo'])->findOrFail($id);
        return response()->json($solicitud);
    }

    public function update(Request $request, $id)
    {
        $solicitud = SolicitudCita::findOrFail($id);

        $request->validate([
            'estado' => 'nullable|in:pendiente,en_revision,atendida,rechazada,convertida_cita',
            'respuesta_admin' => 'nullable|string',
        ]);

        $datos = $request->only(['estado', 'respuesta_admin']);

        if ($request->hasFile('archivo_respuesta')) {
            $archivo = $request->file('archivo_respuesta');
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $archivo->move(public_path('storage/solicitudes_citas'), $nombreArchivo);
            $datos['archivo_respuesta'] = $nombreArchivo;
        }

        if ($request->estado) {
            $datos['fecha_respuesta'] = now();
        }

        $solicitud->update($datos);

        return response()->json([
            'success' => true,
            'data' => $solicitud
        ]);
    }

    public function destroy($id)
    {
        $solicitud = SolicitudCita::findOrFail($id);
        $solicitud->delete();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud eliminada correctamente'
        ]);
    }

    public function validarClienteNIT($nit)
    {
        $cliente = Cliente::where('NIT', $nit)->first();

        if ($cliente) {
            return response()->json([
                'existe' => true,
                'data' => $cliente
            ]);
        }

        return response()->json([
            'existe' => false,
            'data' => null
        ]);
    }

    public function validarEquipoSerial($serial)
    {
        $equipo = Equipo::where('serie', $serial)->first();

        if ($equipo) {
            return response()->json([
                'existe' => true,
                'data' => $equipo
            ]);
        }

        return response()->json([
            'existe' => false,
            'data' => null
        ]);
    }
}
