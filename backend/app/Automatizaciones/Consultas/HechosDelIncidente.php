<?php

namespace App\Automatizaciones\Consultas;

use App\Automatizaciones\Dominio\ConsumoDelPlazo;
use App\Automatizaciones\Dominio\Hechos;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;

// Lo que las condiciones pueden preguntar de un incidente en ese instante: su empresa, categoría, prioridad,
// texto y qué parte de sus plazos ya pasó.
final class HechosDelIncidente
{
    // Columnas que hacen falta, para leer solo esas cuando se evalúan muchos incidentes.
    public const COLUMNAS = [
        'id', 'titulo', 'descripcion', 'cliente_id', 'categoria', 'prioridad', 'created_at',
        'vence_respuesta_en', 'primera_respuesta_en', 'fecha_vencimiento', 'resuelto_en',
    ];

    public static function de(Incidente $incidente, CarbonImmutable $ahora): Hechos
    {
        $creado = $incidente->created_at ?? $ahora;

        return new Hechos(
            $incidente->cliente_id,
            $incidente->categoria,
            $incidente->prioridad,
            "{$incidente->titulo}\n{$incidente->descripcion}",
            ConsumoDelPlazo::porcentaje($creado, $incidente->vence_respuesta_en, $incidente->primera_respuesta_en, $ahora),
            ConsumoDelPlazo::porcentaje($creado, $incidente->fecha_vencimiento, $incidente->resuelto_en, $ahora),
        );
    }
}
