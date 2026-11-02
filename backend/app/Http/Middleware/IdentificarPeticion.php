<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Response;

use function Sentry\configureScope;

// Identificador de correlación: cada petición lleva uno, el que envía el proxy o uno nuevo. Acompaña a todos
// sus registros de log (Context los agrega solo) y vuelve en la respuesta, para rastrear un error de punta a
// punta: quien reporta un fallo da el identificador y se encuentra su rastro.
class IdentificarPeticion
{
    public const CABECERA = 'X-Request-Id';

    // Solo letras, números y separadores: un valor ajeno no puede inyectar líneas ni campos en el log.
    private const FORMATO = '/^[A-Za-z0-9._-]{8,64}$/';

    public function handle(Request $request, Closure $next): Response
    {
        $recibido = (string) $request->headers->get(self::CABECERA);
        $identificador = preg_match(self::FORMATO, $recibido) === 1 ? $recibido : (string) Str::uuid();

        Context::add('peticion', $identificador);
        configureScope(fn (Scope $alcance) => $alcance->setTag('peticion', $identificador));

        $respuesta = $next($request);
        $respuesta->headers->set(self::CABECERA, $identificador);

        return $respuesta;
    }
}
