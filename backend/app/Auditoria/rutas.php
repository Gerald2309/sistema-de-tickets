<?php

use App\Auditoria\Http\Controllers\AuditoriaController;
use Illuminate\Support\Facades\Route;

// Rutas del módulo de auditoría. Las carga routes/api.php dentro del grupo con sesión y permiso declarado.

Route::get('auditoria', AuditoriaController::class)->can('ver_auditoria');
