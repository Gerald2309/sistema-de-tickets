<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// La documentación de la API es la única página que sirve Laravel, y su visor (Scalar) llega de un CDN con versión
// fija y comprobación de integridad (SRI). Esta política le permite cargarse y nada más: no puede enviar
// formularios ni conectarse a otro origen que el propio (ni a los servicios de Scalar), y solo la puede
// incrustar una página del mismo sitio: la documentación técnica del frontend (/documentacion/api).
class CabecerasDeDocumentacion
{
    private const POLITICA = "default-src 'none'; "
        ."script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        ."style-src 'self' 'unsafe-inline'; "
        ."img-src 'self' data:; font-src 'self' data:; connect-src 'self'; "
        ."base-uri 'none'; form-action 'none'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->add([
            'Content-Security-Policy' => self::POLITICA,
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer',
        ]);

        return $respuesta;
    }
}
