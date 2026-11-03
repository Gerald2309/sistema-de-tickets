<?php

use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\AvisoPersonalController;
use App\Identidad\Http\Controllers\ClienteController;
use App\Identidad\Http\Controllers\PerfilController;
use App\Identidad\Http\Controllers\PermisoController;
use App\Identidad\Http\Controllers\PersonaController;
use App\Identidad\Http\Controllers\RolController;
use App\Identidad\Http\Controllers\SesionController;
use App\Identidad\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo de identidad. Las carga routes/api.php dentro del grupo con sesión y permiso declarado.

Route::post('auth/logout', [AuthController::class, 'logout'])->can('autenticado');
Route::get('me', [PerfilController::class, 'mostrar'])->can('autenticado');
Route::put('perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->can('autenticado');
Route::get('perfil/sesiones', [SesionController::class, 'index'])->can('autenticado');
Route::delete('perfil/sesiones', [SesionController::class, 'destroyLasDemas'])->can('autenticado');
Route::delete('perfil/sesiones/{sesion}', [SesionController::class, 'destroy'])->whereNumber('sesion')->can('autenticado');
Route::get('perfil/avisos', [AvisoPersonalController::class, 'index'])->can('autenticado');
Route::post('perfil/avisos/leidos', [AvisoPersonalController::class, 'marcarTodos'])->can('autenticado');
Route::patch('perfil/avisos/{aviso}', [AvisoPersonalController::class, 'marcarLeido'])->whereUuid('aviso')->can('autenticado');

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

// Empresas cliente (F-10). No se borran: se desactivan.
Route::get('clientes', [ClienteController::class, 'index'])->can('consultar_clientes');
Route::post('clientes', [ClienteController::class, 'store'])->can('administrar_usuarios');
Route::patch('clientes/{cliente}', [ClienteController::class, 'update'])->can('administrar_usuarios');

// Personas para asignar o filtrar (solo id, nombre y si está activa).
Route::get('personas', PersonaController::class)->can('consultar_personas');
