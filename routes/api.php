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
use App\Http\Controllers\RepuestoController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\RecibidoFirmaController;
use App\Http\Controllers\SolicitudCitaController;
use App\Http\Controllers\ErroresClienteController;
use App\Http\Controllers\Backoffice\BackofficeDashboardController;
use App\Http\Controllers\Backoffice\BackofficeUsuarioController;
use App\Http\Controllers\Backoffice\BackofficeRolController;
use App\Http\Controllers\Backoffice\BackofficeAuditoriaController;
use App\Http\Controllers\Backoffice\BackofficeErrorController;
use App\Http\Controllers\Backoffice\BackofficeMetricaController;
use App\Http\Controllers\Backoffice\BackofficeLogController;
use App\Http\Controllers\Backoffice\BackofficeBackupController;
use App\Http\Controllers\Backoffice\BackofficeConfigController;

Route::get('/getproductos', [ProductoController::class, 'index']);
Route::get('/getcategorias', [CategoriaController::class, 'index']);
Route::post('/solicitar_cotizacion', [SolicitudesCotizacionController::class, 'store']);

// Solicitudes de cita - público
Route::post('/solicitud_cita', [SolicitudCitaController::class, 'store']);
Route::get('/validar_cliente_nit/{nit}', [SolicitudCitaController::class, 'validarClienteNIT']);
Route::get('/validar_equipo_serial/{serial}', [SolicitudCitaController::class, 'validarEquipoSerial']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/recuperarContraseña', [UserController::class, 'verificacion']);
Route::post('/cambiarContraseña', [UserController::class, 'verificarCodigo']);
Route::post('/aprobarToken', [UserController::class, 'aprobarToken']);
Route::post('/cambiarContraseñaPrimerVez', [UserController::class, 'verificarCodigoPrimerVez']);
Route::post('/primerIngreso', [UserController::class, 'verificarUsuario']);

Route::apiResource('/recibido_firma', RecibidoFirmaController::class);
Route::post('/reporteRealizado/{id}/pdf', [ReporteController::class, 'imprimirConTokenEspecial']);

// Errores reportados por el cliente (Nuxt)
Route::post('/errores-cliente', [ErroresClienteController::class, 'store']);

Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/sesiones', [UserController::class, 'misessiones']);
    Route::apiResource('/productos', ProductoController::class);
    Route::post('/actualiza_productos', [ProductoController::class, 'actualizar']);
    Route::apiResource('/categorias', CategoriaController::class);
    Route::apiResource('/cliente', ClienteController::class);
    Route::apiResource('/solicitud_cotizacion', SolicitudesCotizacionController::class);
    Route::post('/eliminar_cotizacion', [SolicitudesCotizacionController::class, 'eliminar']);
    Route::apiResource('/accesorio', AccesorioController::class);
    Route::apiResource('/actividad', ActividadController::class);
    Route::apiResource('/cita', CitaController::class);
    Route::post('/eliminarCita', [CitaController::class, 'cancelar']);
    Route::apiResource('/componente', ComponenteController::class);
    Route::apiResource('/equipo', EquipoController::class);
    Route::apiResource('/estado_componente', EstadoComponenteController::class);
    Route::apiResource('/material', MaterialController::class);
    Route::apiResource('/medicion', MedicionController::class);
    Route::apiResource('/reporte', ReporteController::class);
    Route::get('/reporte/{id}/pdf', [ReporteController::class, 'imprimir']);
    Route::apiResource('/repuestos', RepuestoController::class);
    Route::apiResource('/sistema', SistemaController::class);
    Route::apiResource('/tecnico', TecnicoController::class);
    Route::apiResource('/tipo_equipo', TipoEquipoController::class);
    Route::apiResource('/rol', RolController::class);
    Route::apiResource('/secciones', SeccionController::class);
    Route::apiResource('/solicitud_cita', SolicitudCitaController::class)->except(['store']);
    Route::post('/solicitud_cita/convertir', [SolicitudCitaController::class, 'convertirEnCita']);
});

/*
|--------------------------------------------------------------------------
| Backoffice (solo SuperAdmin o rol con permiso backoffice_access)
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth:sanctum',
    'check.token.expiration',
    'backoffice.access',
])->prefix('backoffice')->group(function () {
    // Dashboard y sistema
    Route::get('/dashboard', [BackofficeDashboardController::class, 'index']);
    Route::get('/salud', [BackofficeDashboardController::class, 'salud']);

    // Usuarios (con prevención de escalamiento a SuperAdmin)
    Route::middleware('prevenir.escalamiento')->group(function () {
        Route::apiResource('/usuarios', BackofficeUsuarioController::class, [
            'parameters' => ['usuarios' => 'user'],
        ]);
        Route::post('/usuarios/{user}/suspender', [BackofficeUsuarioController::class, 'suspender']);
        Route::post('/usuarios/{user}/reactivar', [BackofficeUsuarioController::class, 'reactivar']);
        Route::get('/usuarios/{user}/sesiones', [BackofficeUsuarioController::class, 'sesiones']);
        Route::delete('/usuarios/{user}/sesiones', [BackofficeUsuarioController::class, 'revocarSesiones']);
    });

    // Roles y permisos (con prevención de escalamiento a SuperAdmin)
    Route::middleware('prevenir.escalamiento')->group(function () {
        Route::get('/roles', [BackofficeRolController::class, 'index']);
        Route::post('/roles', [BackofficeRolController::class, 'store']);
        Route::get('/roles/secciones', [BackofficeRolController::class, 'secciones']);
        Route::get('/roles/{rol}', [BackofficeRolController::class, 'show']);
        Route::put('/roles/{rol}', [BackofficeRolController::class, 'update']);
        Route::delete('/roles/{rol}', [BackofficeRolController::class, 'destroy']);
        Route::get('/roles/{rol}/permisos', [BackofficeRolController::class, 'permisos']);
        Route::put('/roles/{rol}/permisos', [BackofficeRolController::class, 'sincronizarPermisos']);
    });

    // Auditoría
    Route::get('/auditoria', [BackofficeAuditoriaController::class, 'index']);
    Route::get('/auditoria/acciones', [BackofficeAuditoriaController::class, 'acciones']);
    Route::get('/auditoria/{id}', [BackofficeAuditoriaController::class, 'show']);
    Route::post('/auditoria/{id}/restaurar', [BackofficeAuditoriaController::class, 'respaldo']);

    // Errores
    Route::get('/errores', [BackofficeErrorController::class, 'index']);
    Route::get('/errores/resumen', [BackofficeErrorController::class, 'resumen']);
    Route::get('/errores/{id}', [BackofficeErrorController::class, 'show']);
    Route::put('/errores/{id}/resolver', [BackofficeErrorController::class, 'resolver']);
    Route::put('/errores/{id}/reabrir', [BackofficeErrorController::class, 'reabrir']);

    // Métricas y sesiones
    Route::get('/metricas', [BackofficeMetricaController::class, 'index']);
    Route::get('/metricas/rutas', [BackofficeMetricaController::class, 'rutas']);
    Route::get('/metricas/sesiones', [BackofficeMetricaController::class, 'sesiones']);
    Route::delete('/metricas/sesiones', [BackofficeMetricaController::class, 'podarSesiones']);

    // Logs
    Route::get('/logs', [BackofficeLogController::class, 'index']);
    Route::delete('/logs', [BackofficeLogController::class, 'limpiar']);

    // Respaldos
    Route::get('/backup', [BackofficeBackupController::class, 'index']);
    Route::get('/backup/archivos', [BackofficeBackupController::class, 'archivos']);
    Route::post('/backup/exportar', [BackofficeBackupController::class, 'exportar']);
    Route::post('/backup/registro', [BackofficeBackupController::class, 'guardarRespaldo']);
    Route::post('/backup/poda', [BackofficeBackupController::class, 'poda']);

    // Configuración
    Route::get('/config', [BackofficeConfigController::class, 'index']);
    Route::put('/config', [BackofficeConfigController::class, 'update']);
});