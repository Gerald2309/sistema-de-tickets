<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Las respuestas de la API son privadas (el login entrega un token): no se almacenan en caché.
class CabecerasDeApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('Cache-Control', 'no-store');

        return $respuesta;
    }
}
