<?php

use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Toda ruta autenticada declara su permiso con ->can(). Sin él, ExigirAutorizacionDeclarada responde 403.
    Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->can('autenticado');
        Route::put('perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->can('autenticado');
    });
});
