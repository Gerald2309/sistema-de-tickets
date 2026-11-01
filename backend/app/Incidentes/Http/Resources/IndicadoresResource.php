<?php

namespace App\Incidentes\Http\Resources;

use App\Incidentes\Dominio\IndicadoresDelPanel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property IndicadoresDelPanel $resource
 */
class IndicadoresResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $limites = [...IndicadoresDelPanel::TRAMOS_DE_ESPERA, null];

        return [
            'espera' => array_map(
                fn (?int $hastaDias, int $cantidad) => ['hasta_dias' => $hastaDias, 'cantidad' => $cantidad],
                $limites,
                $this->resource->espera,
            ),
            'tendencia' => $this->resource->tendencia,
            'resolucion' => [
                'dias' => IndicadoresDelPanel::DIAS_DE_RESOLUCION,
                'mediana_segundos' => $this->resource->medianaDeResolucion,
                'mediana_anterior_segundos' => $this->resource->medianaAnterior,
                'a_tiempo' => $this->resource->aTiempo,
                'por_prioridad' => array_map(fn (array $datos) => [
                    'mediana_segundos' => $datos['mediana'],
                    'a_tiempo' => $datos['a_tiempo'],
                    'cantidad' => $datos['cantidad'],
                ], $this->resource->porPrioridad),
                'hasta_atender_segundos' => $this->resource->medianaHastaAtender,
                'devueltos' => $this->resource->devueltos,
            ],
        ];
    }
}
