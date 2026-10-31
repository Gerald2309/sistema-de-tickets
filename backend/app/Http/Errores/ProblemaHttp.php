<?php

namespace App\Http\Errores;

use App\Http\Middleware\CabecerasDeApi;
use App\Identidad\Dominio\Excepciones\RolEnUso;
use App\Identidad\Dominio\Excepciones\UltimoAdministrador;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\ConversacionCerrada;
use App\Incidentes\Dominio\Excepciones\NoCabeEnElDia;
use App\Incidentes\Dominio\Excepciones\NoSePuedeConfirmar;
use App\Incidentes\Dominio\Excepciones\NoSePuedeTomar;
use App\Incidentes\Dominio\Excepciones\TransicionInvalida;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

// Traduce cualquier excepción de la API a un problema HTTP según RFC 9457 (application/problem+json).
final class ProblemaHttp
{
    private const DETALLES = [
        401 => 'Falta la credencial o no es válida.',
        403 => 'No tiene permiso para realizar esta acción.',
        404 => 'El recurso no existe o no está disponible.',
        405 => 'Método no permitido para esta ruta.',
        413 => 'El envío supera el tamaño máximo permitido.',
        429 => 'Demasiadas peticiones. Intente de nuevo más tarde.',
    ];

    // Con la depuración activa (solo desarrollo) un error inesperado se deja al manejador por defecto.
    public static function desde(Throwable $excepcion): ?JsonResponse
    {
        return match (true) {
            $excepcion instanceof ValidationException => self::respuesta(
                422,
                'Los datos enviados no son válidos.',
                tipo: 'urn:incidentes:problema:validacion',
                titulo: 'Error de validación',
                extension: ['errors' => $excepcion->errors()],
            ),
            $excepcion instanceof TransicionInvalida => self::respuesta(
                422,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:transicion-invalida',
                titulo: 'Transición de estado no permitida',
                extension: ['estados_permitidos' => array_map(fn (Estado $estado) => $estado->value, $excepcion->desde->siguientes())],
            ),
            $excepcion instanceof NoSePuedeTomar => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:no-se-puede-tomar',
                titulo: 'El incidente no se puede tomar',
            ),
            $excepcion instanceof NoCabeEnElDia => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:no-cabe-en-el-dia',
                titulo: 'No cabe en el día',
            ),
            $excepcion instanceof NoSePuedeConfirmar => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:no-se-puede-confirmar',
                titulo: 'La solución no se puede confirmar',
            ),
            $excepcion instanceof ConversacionCerrada => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:conversacion-cerrada',
                titulo: 'Conversación cerrada',
            ),
            $excepcion instanceof UltimoAdministrador => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:ultimo-administrador',
                titulo: 'Operación no permitida: último administrador',
            ),
            $excepcion instanceof RolEnUso => self::respuesta(
                409,
                $excepcion->getMessage(),
                tipo: 'urn:incidentes:problema:rol-en-uso',
                titulo: 'Rol en uso',
            ),
            $excepcion instanceof AuthenticationException => self::respuesta(
                401,
                self::DETALLES[401],
                cabeceras: ['WWW-Authenticate' => 'Bearer'],
            ),
            $excepcion instanceof HttpExceptionInterface => self::respuesta(
                $excepcion->getStatusCode(),
                self::DETALLES[$excepcion->getStatusCode()] ?? Response::$statusTexts[$excepcion->getStatusCode()] ?? 'Error.',
                cabeceras: $excepcion->getHeaders(),
            ),
            config('app.debug') => null,
            // Sin detalles internos: solo el identificador con el que se encuentra el error en el log.
            default => self::respuesta(500, 'Ocurrió un error inesperado.', extension: ['peticion' => Context::get('peticion')]),
        };
    }

    /**
     * @param  array<string, mixed>  $extension
     * @param  array<string, string>  $cabeceras
     */
    private static function respuesta(
        int $estado,
        string $detalle,
        string $tipo = 'about:blank',
        ?string $titulo = null,
        array $extension = [],
        array $cabeceras = [],
    ): JsonResponse {
        $cuerpo = [
            'type' => $tipo,
            'title' => $titulo ?? Response::$statusTexts[$estado] ?? 'Error',
            'status' => $estado,
            'detail' => $detalle,
            ...$extension,
        ];

        return response()->json($cuerpo, $estado, $cabeceras + ['Content-Type' => 'application/problem+json'] + CabecerasDeApi::CABECERAS, JSON_UNESCAPED_UNICODE);
    }
}
