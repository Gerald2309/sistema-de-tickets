<?php

namespace App\Automatizaciones\Consultas;

use App\Automatizaciones\Dominio\Hechos;
use App\Incidentes\Models\Incidente;

// Lo que las condiciones pueden preguntar de un incidente: su empresa, categoría, prioridad y texto.
final class HechosDelIncidente
{
    // Columnas que hacen falta, para leer solo esas cuando se evalúan muchos incidentes.
    public const COLUMNAS = ['id', 'titulo', 'descripcion', 'cliente_id', 'categoria', 'prioridad'];

    public static function de(Incidente $incidente): Hechos
    {
        return new Hechos($incidente->cliente_id, $incidente->categoria, $incidente->prioridad, "{$incidente->titulo}\n{$incidente->descripcion}");
    }
}
