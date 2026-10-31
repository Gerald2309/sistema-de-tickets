<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Las respuestas de la API son privadas (el login entrega un token) y nunca son una página: no se
// guardan en caché, no se adivina su tipo y no se pueden incrustar (OWASP, REST Security Cheat Sheet).
// Las respuestas de error las arma ProblemaHttp con estas mismas cabeceras.
class CabecerasDeApi
{
    public const CABECERAS = [
        'Cache-Control' => 'no-store',
        'X-Content-Type-Options' => 'nosniff',
        'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'none'",
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'no-referrer',
        'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        foreach (self::CABECERAS as $nombre => $valor) {
            $respuesta->headers->set($nombre, $valor);
        }

        return $respuesta;
    }
}
