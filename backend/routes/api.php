<?php

use App\Identidad\Http\Controllers\AuthController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

// Entrada de la API: aplica el middleware común una sola vez y reúne las rutas de cada módulo, que viven en
// app/{Módulo}/rutas.php junto al resto del módulo.
Route::prefix('v1')->group(function () {
    // La única ruta sin sesión.
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Toda ruta autenticada declara su permiso con ->can(). Sin él, ExigirAutorizacionDeclarada responde 403.
    Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
        // Autorización de los canales privados del tiempo real (F-12); cada canal decide en routes/channels.php.
        Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])->can('autenticado');

        foreach (['Identidad', 'Incidentes', 'Automatizaciones', 'Auditoria'] as $modulo) {
            require app_path("{$modulo}/rutas.php");
        }
    });
});
