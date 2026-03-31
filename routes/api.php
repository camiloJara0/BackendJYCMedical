<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SolicitudesCotizacionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AccesorioController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ComponenteController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\EstadoComponenteController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MedicionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RepuestosController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\TipoEquipoController;

Route::get('/getproductos', [ProductoController::class, 'index']);
Route::get('/getcategorias', [CategoriaController::class, 'index']);
Route::post('/solicitar_cotizacion', [SolicitudesCotizacionController::class, 'store']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/recuperarContraseña', [UserController::class, 'verificacion']);
Route::post('/cambiarContraseña', [UserController::class, 'verificarCodigo']);
Route::post('/cambiarContraseñaPrimerVez', [UserController::class, 'verificarCodigoPrimerVez']);
Route::post('/primerIngreso', [UserController::class, 'verificarUsuario']);

Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::apiResource('/productos', ProductoController::class);
    Route::post('/actualiza_productos', [ProductoController::class, 'actualizar']);
    Route::apiResource('/categorias', CategoriaController::class);
    Route::apiResource('/cliente', ClienteController::class);
    Route::apiResource('/solicitud_cotizacion', SolicitudesCotizacionController::class);
    Route::post('/eliminar_cotizacion', [SolicitudesCotizacionController::class, 'eliminar']);
    Route::apiResource('/accesorio', AccesorioController::class);
    Route::apiResource('/actividad', ActividadController::class);
    Route::apiResource('/cita', CitaController::class);
    Route::apiResource('/componente', ComponenteController::class);
    Route::apiResource('/equipo', EquipoController::class);
    Route::apiResource('/estado_componente', EstadoComponenteController::class);
    Route::apiResource('/material', MaterialController::class);
    Route::apiResource('/medicion', MedicionController::class);
    Route::apiResource('/reporte', ReporteController::class);
    Route::get('/reporte/{id}/pdf', [ReporteController::class, 'imprimir']);
    Route::apiResource('/repuestos', RepuestosController::class);
    Route::apiResource('/sistema', SistemaController::class);
    Route::apiResource('/tecnico', TecnicoController::class);
    Route::apiResource('/tipo_equipo', TipoEquipoController::class);
});