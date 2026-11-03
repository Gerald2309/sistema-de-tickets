<?php

namespace App\Automatizaciones\Dominio;

// Cuál de los dos plazos del acuerdo de servicio mira un disparador.
enum PlazoDelSla: string
{
    case Respuesta = 'respuesta';
    case Resolucion = 'resolucion';
}
