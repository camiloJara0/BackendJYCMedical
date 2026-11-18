<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tecnico;
use App\Models\CodigoVerificacion;
use App\Mail\CodigoVerificacionMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Request\Login;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contraseña' => 'required',
        ]);

        $user = User::where('correo', $request->correo)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'type'    => 'USER_NOT_FOUND',
                'message' => 'El correo no se encuentra registrado'
            ], 200);
        }

        if ($user->estado == 'inactivo'){
            return response()->json([
                'success' => false,
                'type'    => 'USER_DELETE',
                'message' => 'Usuario Eliminado'
            ], 200);
        }

        if (!Hash::check($request->contraseña, $user->contraseña)) {
            return response()->json([
                'success' => false,
                'type'    => 'INVALID_PASSWORD',
                'message' => 'La contraseña es incorrecta'
            ], 200);
        }

        $tokenResult = $user->createToken('auth_token');

        // Establece la expiración
        $accessToken = $tokenResult->accessToken;
        $accessToken->expires_at = now()->addHours((int) config('backoffice.ajustes.duracion_token_horas', 16));
        $accessToken->ip_address = $request->ip();
        $accessToken->user_agent = Str::limit((string) $request->userAgent(), 250, '');
        $accessToken->save();

        $user->ultimo_acceso_at = now();
        $user->save();

        if ((bool) config('backoffice.ajustes.podar_sesiones_en_login', true)) {
            $this->podarSesiones($user);
        }

        // Obtén el token en texto plano
        $token = $tokenResult->plainTextToken;

        $tecnico_id = '';
        if ($user->rol == 'Tecnico'){
            $tecnico = Tecnico::where('user_id', $user->id)->first();

            $tecnico_id = $tecnico->id;
        }

        \App\Services\Auditoria::registrar($request, 'login', 'users', $user->id, null, [
            'correo' => $user->correo,
            'rol' => $user->rol,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'access_token' => $token,
            'user' => [
                'correo' => $user->correo,
                'rol' => $user->rol,
                'name' => $user->nombre,
                'tecnico_id' => $tecnico_id,
            ],
        ]);

    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            \App\Services\Auditoria::registrar($request, 'logout', 'personal_access_tokens', $token->id, [
                'name' => $token->name,
            ], null);

            $token->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);
    }

    public function misessiones(Request $request)
    {
        $user = $request->user();

        $sesiones = \DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'ip_address', 'user_agent', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $sesiones,
        ]);
    }

    protected function podarSesiones(User $user): void
    {
        $maximo = max(1, (int) config('backoffice.ajustes.sesiones_maximas_por_usuario', 5));

        $vigentes = \DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->pluck('id');

        if ($vigentes->count() > $maximo) {
            $sobrantes = $vigentes->slice($maximo)->values()->all();

            \DB::table('personal_access_tokens')->whereIn('id', $sobrantes)->delete();
        }

        \DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '<', now());
            })
            ->delete();
    }

    public function verificacion(Request $request)
    {
        $request->validate(['correo' => 'required|email']);

        $usuario = User::where('correo', $request->correo)->first();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Correo no registrado'
            ]);
        }

        $codigo = Str::random(6);

        CodigoVerificacion::create([
            'correo' => $usuario->correo,
            'codigo' => $codigo,
            'expira_en' => Carbon::now()->addMinutes(240)
        ]);

        Mail::to($usuario->correo)->send(new CodigoVerificacionMail($usuario->correo, $codigo));

        return response()->json([
            'success' => true,
            'message' => 'Correo enviado con código de verificación'
        ]);
    }

    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'codigo' => 'required|string',
            'contraseña' => 'required|min:6'
        ]);

        $registro = CodigoVerificacion::where('correo', $request->correo)
            ->where('codigo', $request->codigo)
            ->where('usado', false)
            ->where('expira_en', '>', now())
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Código inválido o expirado'], 401);
        }

        $usuario = User::where('correo', $request->correo)->first();
        $usuario->contraseña = Hash::make($request->contraseña);
        $usuario->save();

        $registro->usado = true;
        $registro->save();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }

    public function verificarCodigoPrimerVez(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string',
            'contraseña' => 'required|min:6'
        ]);

        $correo = CodigoVerificacion::where('codigo', $request->codigo)->first();

        $registro = CodigoVerificacion::where('correo', $correo->correo)
            ->where('codigo', $request->codigo)
            ->where('usado', false)
            ->where('expira_en', '>', now())
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Código inválido o expirado'], 401);
        }

        $usuario = User::where('correo', $correo->correo)->first();
        $usuario->contraseña = Hash::make($request->contraseña);
        $usuario->save();

        $registro->usado = true;
        $registro->save();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }

    public function verificarUsuario(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $user = User::where('correo', $request->correo)->first();

        return response()->json([
            'success' => (bool) $user,
            'primer_ingreso' => (bool) ($user && empty($user->contraseña)),
            'message' => $user ? 'Usuario encontrado' : 'El correo no se encuentra registrado',
        ], 200);
    }

    public function aprobarToken(Request $request)
    {
        DB::beginTransaction();

        try {
            $hashed = hash('sha256', $request->token);
            $token = DB::table('personal_access_tokens')
                ->where('token', $hashed)
                ->first();

            if (!$token || $token->expires_at < now()) {
                throw new \Exception("Solicitud inválida o ya usada");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'id' => $token->tokenable_id,
                'token' => $hashed
            ], 200) ;

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
