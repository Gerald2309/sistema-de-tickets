<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// La documentación de la API es la única página que sirve Laravel, y su visor (Stoplight Elements) llega de un CDN
// con versión fija y comprobación de integridad (SRI). Esta política le permite cargarse y nada más: no puede
// enviar formularios, ni incrustarse, ni conectarse a otro origen que el propio.
class CabecerasDeDocumentacion
{
    private const POLITICA = "default-src 'none'; "
        ."script-src 'self' 'unsafe-inline' https://unpkg.com; "
        ."style-src 'self' 'unsafe-inline' https://unpkg.com; "
        ."img-src 'self' data:; font-src 'self' data: https://unpkg.com; connect-src 'self'; "
        ."base-uri 'none'; form-action 'none'; frame-ancestors 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->add([
            'Content-Security-Policy' => self::POLITICA,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'no-referrer',
        ]);

        return $respuesta;
    }
}
