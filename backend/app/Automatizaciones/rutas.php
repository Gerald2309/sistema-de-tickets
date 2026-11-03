<?php

use App\Automatizaciones\Http\Controllers\AutomatizacionController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo de automatizaciones: reglas que actúan sobre los incidentes a nombre de quien las guarda.
// Las carga routes/api.php dentro del grupo con sesión y permiso declarado.

Route::get('automatizaciones', [AutomatizacionController::class, 'index'])->can('administrar_automatizaciones');
Route::post('automatizaciones', [AutomatizacionController::class, 'store'])->can('administrar_automatizaciones');
Route::post('automatizaciones/prueba', [AutomatizacionController::class, 'probar'])->can('administrar_automatizaciones');
Route::get('automatizaciones/{automatizacion}', [AutomatizacionController::class, 'show'])->whereNumber('automatizacion')->can('administrar_automatizaciones');
Route::put('automatizaciones/{automatizacion}', [AutomatizacionController::class, 'update'])->whereNumber('automatizacion')->can('administrar_automatizaciones');
Route::patch('automatizaciones/{automatizacion}/estado', [AutomatizacionController::class, 'cambiarEstado'])->whereNumber('automatizacion')->can('administrar_automatizaciones');
Route::delete('automatizaciones/{automatizacion}', [AutomatizacionController::class, 'destroy'])->whereNumber('automatizacion')->can('administrar_automatizaciones');
