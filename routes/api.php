<?php

use App\Auditoria\Http\Controllers\AuditoriaController;
use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\PerfilController;
use App\Identidad\Http\Controllers\PermisoController;
use App\Identidad\Http\Controllers\RolController;
use App\Identidad\Http\Controllers\UsuarioController;
use App\Incidentes\Http\Controllers\AvisosController;
use App\Incidentes\Http\Controllers\CatalogoController;
use App\Incidentes\Http\Controllers\DashboardController;
use App\Incidentes\Http\Controllers\HistorialController;
use App\Incidentes\Http\Controllers\IncidenteController;
use App\Incidentes\Http\Controllers\SimilaresController;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Toda ruta autenticada declara su permiso con ->can(). Sin él, ExigirAutorizacionDeclarada responde 403.
    Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->can('autenticado');
        Route::put('perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->can('autenticado');

        Route::get('avisos', AvisosController::class)->can('verAvisos', Incidente::class);
        Route::get('dashboard', DashboardController::class)->can('verDashboard', Incidente::class);
        Route::get('catalogos', CatalogoController::class)->can('autenticado');

        Route::get('incidentes', [IncidenteController::class, 'index'])->can('listar', Incidente::class);
        Route::post('incidentes', [IncidenteController::class, 'store'])->can('crear', Incidente::class);
        Route::get('incidentes/{incidente}', [IncidenteController::class, 'show'])->can('ver', 'incidente');
        Route::patch('incidentes/{incidente}', [IncidenteController::class, 'update'])->can('actualizar', 'incidente');
        Route::delete('incidentes/{incidente}', [IncidenteController::class, 'destroy'])->can('eliminar', 'incidente');

        Route::get('incidentes/{incidente}/similares', SimilaresController::class)->can('verSimilares', 'incidente');

        Route::get('usuarios', [UsuarioController::class, 'index'])->can('administrar_usuarios');
        Route::post('usuarios', [UsuarioController::class, 'store'])->can('administrar_usuarios');
        Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->can('administrar_usuarios');
        Route::patch('usuarios/{usuario}', [UsuarioController::class, 'update'])->can('administrar_usuarios');
        Route::put('usuarios/{usuario}/rol', [UsuarioController::class, 'asignarRol'])->can('administrar_usuarios');

        // Quien administra usuarios necesita ver los roles y permisos para asignarlos.
        Route::get('roles', [RolController::class, 'index'])->can('consultar_roles');
        Route::get('roles/{rol}', [RolController::class, 'show'])->can('consultar_roles');
        Route::post('roles', [RolController::class, 'store'])->can('administrar_roles');
        Route::patch('roles/{rol}', [RolController::class, 'update'])->can('administrar_roles');
        Route::delete('roles/{rol}', [RolController::class, 'destroy'])->can('administrar_roles');
        Route::put('roles/{rol}/permisos', [RolController::class, 'asignarPermisos'])->can('administrar_roles');
        Route::get('permisos', PermisoController::class)->can('consultar_roles');

        Route::get('auditoria', AuditoriaController::class)->can('ver_auditoria');

        // El historial de un incidente borrado sigue consultable (RF-08, RF-18).
        Route::get('incidentes/{incidente}/historial', HistorialController::class)
            ->withTrashed()
            ->can('verHistorial', 'incidente');
    });
});
