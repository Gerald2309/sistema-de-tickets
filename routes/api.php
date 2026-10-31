<?php

use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\PerfilController;
use App\Incidentes\Http\Controllers\CatalogoController;
use App\Incidentes\Http\Controllers\HistorialController;
use App\Incidentes\Http\Controllers\IncidenteController;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Toda ruta autenticada declara su permiso con ->can(). Sin él, ExigirAutorizacionDeclarada responde 403.
    Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->can('autenticado');
        Route::put('perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->can('autenticado');

        Route::get('catalogos', CatalogoController::class)->can('autenticado');

        Route::get('incidentes', [IncidenteController::class, 'index'])->can('listar', Incidente::class);
        Route::post('incidentes', [IncidenteController::class, 'store'])->can('crear', Incidente::class);
        Route::get('incidentes/{incidente}', [IncidenteController::class, 'show'])->can('ver', 'incidente');
        Route::patch('incidentes/{incidente}', [IncidenteController::class, 'update'])->can('actualizar', 'incidente');
        Route::delete('incidentes/{incidente}', [IncidenteController::class, 'destroy'])->can('eliminar', 'incidente');

        // El historial de un incidente borrado sigue consultable (RF-08, RF-18).
        Route::get('incidentes/{incidente}/historial', HistorialController::class)
            ->withTrashed()
            ->can('verHistorial', 'incidente');
    });
});
