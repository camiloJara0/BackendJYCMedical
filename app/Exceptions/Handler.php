<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Str;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->registrarErrorAplicacion($e);
        });
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        return redirect()->guest('/');
    }

    protected function registrarErrorAplicacion(Throwable $e): void
    {
        try {
            if (app()->runningInConsole() && !app()->environment('testing')) {
                return;
            }

            $request = request();

            if (!$request || !$request->is('api/*')) {
                return;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return;
            }

            $mensaje = Str::limit($e->getMessage(), 480, '');
            $hash = sha1($mensaje . '|' . get_class($e) . '|' . $request->path());

            $existente = \DB::table('app_errors')->where('hash', $hash)->first();

            if ($existente) {
                \DB::table('app_errors')->where('id', $existente->id)->update([
                    'veces' => $existente->veces + 1,
                    'updated_at' => now(),
                ]);

                return;
            }

            \DB::table('app_errors')->insert([
                'user_id' => $request->user()?->id,
                'nivel' => 'error',
                'mensaje' => $mensaje,
                'contexto' => json_encode([
                    'clase' => get_class($e),
                    'metodo' => $request->method(),
                ], JSON_UNESCAPED_UNICODE),
                'stack_trace' => Str::limit($e->getTraceAsString(), 9800, ''),
                'archivo' => Str::limit((string) $e->getFile(), 250, ''),
                'linea' => $e->getLine(),
                'ruta' => Str::limit('/' . $request->path(), 250, ''),
                'metodo' => $request->method(),
                'ip' => $request->ip(),
                'estado_http' => 500,
                'hash' => $hash,
                'veces' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $ignored) {
            // Nunca debe romper el manejo de errores principal
        }
    }
}
