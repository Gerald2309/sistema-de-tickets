<?php

use App\Auditoria\Http\Controllers\AuditoriaController;
use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\ClienteController;
use App\Identidad\Http\Controllers\PerfilController;
use App\Identidad\Http\Controllers\PermisoController;
use App\Identidad\Http\Controllers\PersonaController;
use App\Identidad\Http\Controllers\RolController;
use App\Identidad\Http\Controllers\UsuarioController;
use App\Incidentes\Http\Controllers\AdjuntoController;
use App\Incidentes\Http\Controllers\AvisosController;
use App\Incidentes\Http\Controllers\BloqueoController;
use App\Incidentes\Http\Controllers\CatalogoController;
use App\Incidentes\Http\Controllers\ConfirmarSolucionController;
use App\Incidentes\Http\Controllers\DashboardController;
use App\Incidentes\Http\Controllers\ExportacionController;
use App\Incidentes\Http\Controllers\HistorialController;
use App\Incidentes\Http\Controllers\IncidenteController;
use App\Incidentes\Http\Controllers\IncidenteLoteController;
use App\Incidentes\Http\Controllers\LecturaController;
use App\Incidentes\Http\Controllers\MensajeController;
use App\Incidentes\Http\Controllers\NotaPersonalController;
use App\Incidentes\Http\Controllers\PlanController;
use App\Incidentes\Http\Controllers\PorPlanearController;
use App\Incidentes\Http\Controllers\SimilaresController;
use App\Incidentes\Http\Controllers\SugerenciaDelDiaController;
use App\Incidentes\Http\Controllers\TomarIncidenteController;
use App\Incidentes\Http\Controllers\TraspasoController;
use App\Incidentes\Http\Controllers\UltimoTrabajadoController;
use App\Incidentes\Models\Incidente;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Toda ruta autenticada declara su permiso con ->can(). Sin él, ExigirAutorizacionDeclarada responde 403.
    Route::middleware(['auth:sanctum', 'throttle:api', 'autorizacion.declarada'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->can('autenticado');
        Route::get('me', [PerfilController::class, 'mostrar'])->can('autenticado');
        Route::put('perfil/contrasena', [PerfilController::class, 'cambiarContrasena'])->can('autenticado');

        Route::get('avisos', AvisosController::class)->can('verAvisos', Incidente::class);
        Route::get('dashboard', DashboardController::class)->can('verDashboard', Incidente::class);
        Route::get('catalogos', CatalogoController::class)->can('autenticado');

        // Autorización de los canales privados del tiempo real (F-12); cada canal decide en routes/channels.php.
        Route::post('broadcasting/auth', [BroadcastController::class, 'authenticate'])->can('autenticado');

        Route::get('incidentes', [IncidenteController::class, 'index'])->can('listar', Incidente::class);
        Route::post('incidentes', [IncidenteController::class, 'store'])->can('crear', Incidente::class);
        // Las rutas de «exportar» y de lote van antes de las de un incidente: no son un id.
        Route::get('incidentes/exportar', ExportacionController::class)->can('listar', Incidente::class);
        Route::patch('incidentes/lote', [IncidenteLoteController::class, 'actualizar'])->can('actualizarEnLote', Incidente::class);
        Route::delete('incidentes/lote', [IncidenteLoteController::class, 'eliminar'])->can('eliminarEnLote', Incidente::class);

        Route::get('incidentes/{incidente}', [IncidenteController::class, 'show'])->can('ver', 'incidente');
        Route::patch('incidentes/{incidente}', [IncidenteController::class, 'update'])->can('actualizar', 'incidente');
        Route::delete('incidentes/{incidente}', [IncidenteController::class, 'destroy'])->can('eliminar', 'incidente');

        // Tomar un incidente libre para atenderlo: queda asignado a quien lo toma.
        Route::post('incidentes/{incidente}/tomar', TomarIncidenteController::class)->can('tomar', 'incidente');
        // Quien lo reportó confirma si la solución sirvió: lo cierra o lo devuelve a soporte con el motivo.
        Route::post('incidentes/{incidente}/confirmacion', ConfirmarSolucionController::class)->can('confirmarSolucion', 'incidente');

        // Conversación del incidente: la lee quien lo ve; escriben quien lo reportó y quien lo atiende.
        Route::get('incidentes/{incidente}/mensajes', [MensajeController::class, 'index'])->can('ver', 'incidente');
        Route::post('incidentes/{incidente}/mensajes', [MensajeController::class, 'store'])->can('escribirMensaje', 'incidente');
        // Quien la lee la deja leída hasta el último mensaje: lo que llegue después cuenta como sin leer.
        Route::put('incidentes/{incidente}/lectura', LecturaController::class)->can('ver', 'incidente');
        Route::get('incidentes/{incidente}/mensajes/{mensaje}/adjuntos/{adjunto}', AdjuntoController::class)
            ->scopeBindings()
            ->can('verAdjunto', ['incidente', 'mensaje']);

        Route::get('incidentes/{incidente}/similares', SimilaresController::class)->can('verSimilares', 'incidente');

        // «Mi día»: el espacio personal de quien atiende incidentes. Cada persona solo alcanza lo suyo.
        Route::prefix('mi-dia')->group(function () {
            Route::get('plan', [PlanController::class, 'index'])->can('planearSuDia', Incidente::class);
            Route::post('plan', [PlanController::class, 'store'])->can('planearSuDia', Incidente::class);
            Route::patch('plan/{entrada}', [PlanController::class, 'update'])->whereNumber('entrada')->can('planearSuDia', Incidente::class);
            Route::delete('plan/{entrada}', [PlanController::class, 'destroy'])->whereNumber('entrada')->can('planearSuDia', Incidente::class);
            Route::post('sugerencia', SugerenciaDelDiaController::class)->can('planearSuDia', Incidente::class);
            Route::get('por-planear', [PorPlanearController::class, 'index'])->can('planearSuDia', Incidente::class);
            Route::get('pendientes', [PorPlanearController::class, 'pendientes'])->can('planearSuDia', Incidente::class);
            Route::get('ultimo', UltimoTrabajadoController::class)->can('planearSuDia', Incidente::class);
            Route::get('notas', [NotaPersonalController::class, 'index'])->can('planearSuDia', Incidente::class);
            Route::post('notas', [NotaPersonalController::class, 'store'])->can('planearSuDia', Incidente::class);
            Route::patch('notas/{nota}', [NotaPersonalController::class, 'update'])->whereNumber('nota')->can('planearSuDia', Incidente::class);
            Route::delete('notas/{nota}', [NotaPersonalController::class, 'destroy'])->whereNumber('nota')->can('planearSuDia', Incidente::class);
            Route::put('bloqueos/{incidente}', [BloqueoController::class, 'update'])->can('marcarBloqueo', 'incidente');
            Route::delete('bloqueos/{incidente}', [BloqueoController::class, 'destroy'])->can('marcarBloqueo', 'incidente');
        });

        Route::get('usuarios', [UsuarioController::class, 'index'])->can('administrar_usuarios');
        Route::post('usuarios', [UsuarioController::class, 'store'])->can('administrar_usuarios');
        Route::get('usuarios/{usuario}', [UsuarioController::class, 'show'])->can('administrar_usuarios');
        Route::patch('usuarios/{usuario}', [UsuarioController::class, 'update'])->can('administrar_usuarios');
        Route::put('usuarios/{usuario}/rol', [UsuarioController::class, 'asignarRol'])->can('administrar_usuarios');
        // Antes de desactivar a alguien, sus incidentes sin resolver pasan a otra persona o vuelven a la cola.
        Route::get('usuarios/{usuario}/incidentes-abiertos', [TraspasoController::class, 'show'])->can('administrar_usuarios');
        Route::post('usuarios/{usuario}/incidentes-abiertos/traspaso', [TraspasoController::class, 'store'])->can('administrar_usuarios');

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

        Route::get('auditoria', AuditoriaController::class)->can('ver_auditoria');

        // El historial de un incidente borrado sigue consultable (RF-08, RF-18).
        Route::get('incidentes/{incidente}/historial', HistorialController::class)
            ->withTrashed()
            ->can('verHistorial', 'incidente');
    });
});
