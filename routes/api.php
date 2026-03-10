<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SolicitudesCotizacionController;
use App\Http\Controllers\UserController;

Route::get('/getproductos', [ProductoController::class, 'index']);
Route::get('/getcategorias', [CategoriaController::class, 'index']);
Route::post('/solicitar_cotizacion', [SolicitudesCotizacionController::class, 'store']);
Route::post('/login', [UserController::class, 'login']);

Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::apiResource('/productos', ProductoController::class);
    Route::apiResource('/categorias', CategoriaController::class);
    Route::apiResource('/solicitud_cotizacion', SolicitudesCotizacionController::class);
});