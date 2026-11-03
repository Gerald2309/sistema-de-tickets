<?php

namespace App\Http\Documentacion;

use App\Auditoria\Http\Controllers\AuditoriaController;
use App\Automatizaciones\Http\Controllers\AutomatizacionController;
use App\Identidad\Dominio\ClavePermiso;
use App\Identidad\Http\Controllers\AuthController;
use App\Identidad\Http\Controllers\ClienteController;
use App\Identidad\Http\Controllers\PerfilController;
use App\Identidad\Http\Controllers\PermisoController;
use App\Identidad\Http\Controllers\PersonaController;
use App\Identidad\Http\Controllers\RolController;
use App\Identidad\Http\Controllers\SesionController;
use App\Identidad\Http\Controllers\UsuarioController;
use App\Incidentes\Http\Controllers\AcuerdoDeServicioController;
use App\Incidentes\Http\Controllers\AdjuntoController;
use App\Incidentes\Http\Controllers\CatalogoController;
use App\Incidentes\Http\Controllers\ConfirmarSolucionController;
use App\Incidentes\Http\Controllers\DashboardController;
use App\Incidentes\Http\Controllers\ExportacionController;
use App\Incidentes\Http\Controllers\HistorialController;
use App\Incidentes\Http\Controllers\IncidenteController;
use App\Incidentes\Http\Controllers\IncidenteLoteController;
use App\Incidentes\Http\Controllers\IndicadoresController;
use App\Incidentes\Http\Controllers\MensajeController;
use App\Incidentes\Http\Controllers\SimilaresController;
use App\Incidentes\Http\Controllers\TomarIncidenteController;
use App\Incidentes\Http\Controllers\TraspasoController;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Tag;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;

/**
 * Cómo se presenta la API: por recurso, en este orden, y solo lo que sirve a quien se integra. Lo que no está aquí
 * lo usa únicamente la interfaz (avisos, marcas de lectura, Mi día, tiempo real…): sigue funcionando, pero no se
 * publica.
 */
final class SeccionesDeLaApi
{
    /** @var array<string, array{0: string, 1: list<class-string>}> sección => [descripción, controladores] */
    private const SECCIONES = [
        'Autenticación y cuenta' => [
            'Token de acceso, perfil, contraseña y sesiones abiertas.',
            [AuthController::class, PerfilController::class, SesionController::class],
        ],
        'Incidentes' => [
            'Crear, listar, filtrar, actualizar y cerrar incidentes; acciones en lote, historial y exportación.',
            [
                IncidenteController::class, IncidenteLoteController::class, TomarIncidenteController::class,
                ConfirmarSolucionController::class, HistorialController::class, SimilaresController::class,
                ExportacionController::class, CatalogoController::class,
            ],
        ],
        'Conversación' => [
            'Mensajes entre quien reporta y soporte, con imágenes adjuntas.',
            [MensajeController::class, AdjuntoController::class],
        ],
        'Panel' => [
            'Resumen e indicadores de lo que la persona puede ver.',
            [DashboardController::class, IndicadoresController::class],
        ],
        'Usuarios y roles' => [
            'Cuentas, roles y permisos.',
            [UsuarioController::class, TraspasoController::class, PersonaController::class, RolController::class, PermisoController::class],
        ],
        'Empresas y SLA' => [
            'Empresas cliente y sus plazos de respuesta y resolución.',
            [ClienteController::class, AcuerdoDeServicioController::class],
        ],
        'Automatizaciones' => [
            'Reglas que actúan sobre los incidentes al crearse o al pasar un tiempo, a nombre de quien las guarda.',
            [AutomatizacionController::class],
        ],
        'Auditoría' => [
            'Registro de las acciones sensibles.',
            [AuditoriaController::class],
        ],
    ];

    public static function publica(Route $ruta): bool
    {
        return self::seccionDe($ruta) !== null;
    }

    // Cada operación va en su sección y dice qué autorización exige.
    public static function ubicar(Operation $operacion, RouteInfo $ruta): void
    {
        $operacion->setTags(array_filter([self::seccionDe($ruta->route)]));

        $exige = self::autorizacion($ruta->route);
        if ($exige !== null) {
            $operacion->description(trim($operacion->description."\n\n".$exige));
        }
    }

    // Las secciones aparecen en el orden de SECCIONES, cada una con su descripción.
    public static function ordenar(OpenApi $documento): void
    {
        $documento->tags = array_map(
            fn (string $nombre) => new Tag($nombre, self::SECCIONES[$nombre][0]),
            array_keys(self::SECCIONES),
        );
    }

    private static function seccionDe(Route $ruta): ?string
    {
        $controlador = $ruta->getControllerClass();

        foreach (self::SECCIONES as $nombre => [, $controladores]) {
            if (in_array($controlador, $controladores, true)) {
                return $nombre;
            }
        }

        return null;
    }

    // Lee el ->can() de la ruta: solo haber iniciado sesión, un permiso del catálogo, una regla de la política de
    // incidentes (lleva el modelo como argumento) o una regla compuesta de varios permisos.
    private static function autorizacion(Route $ruta): ?string
    {
        foreach ($ruta->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'can:')) {
                continue;
            }

            $partes = explode(',', substr($middleware, 4));
            $habilidad = $partes[0];

            return match (true) {
                $habilidad === 'autenticado' => '**Requiere:** sesión iniciada.',
                ClavePermiso::tryFrom($habilidad) !== null => "**Requiere:** permiso `{$habilidad}`.",
                count($partes) > 1 => "**Requiere:** regla `{$habilidad}` de incidentes (según los permisos y lo que la persona puede ver).",
                default => "**Requiere:** regla `{$habilidad}` (alguno de los permisos que la componen).",
            };
        }

        return null;
    }
}
