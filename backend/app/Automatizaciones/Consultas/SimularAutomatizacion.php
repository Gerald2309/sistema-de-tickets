<?php

namespace App\Automatizaciones\Consultas;

use App\Automatizaciones\Dominio\Accion;
use App\Automatizaciones\Dominio\Flujo;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;

// Qué habría hecho un flujo con los incidentes creados en los últimos días: por qué camino salió cada uno y
// cuántos habrían recibido acciones. Solo lee: no cambia nada. Evalúa las condiciones, no el disparador.
class SimularAutomatizacion
{
    public const DIAS = 30;

    // Los más recientes primero; con más que esto, la muestra ya dice lo que hace falta.
    public const MAXIMO = 5000;

    private const EJEMPLOS = 5;

    /**
     * @return array{
     *     dias: int,
     *     evaluados: int,
     *     con_acciones: int,
     *     caminos: list<array{decisiones: list<array{campo: string, cumple: bool}>, acciones: list<string>, cantidad: int}>,
     *     ejemplos: list<array{id: int, codigo: string, titulo: string}>,
     * }
     */
    public function ejecutar(Flujo $flujo, CarbonImmutable $ahora): array
    {
        $caminos = [];
        $ejemplos = [];
        $evaluados = 0;
        $conAcciones = 0;

        $incidentes = Incidente::query()
            ->withoutEagerLoads()
            ->select(HechosDelIncidente::COLUMNAS)
            ->where('created_at', '>=', $ahora->subDays(self::DIAS))
            ->orderByDesc('id')
            ->limit(self::MAXIMO)
            ->cursor();

        foreach ($incidentes as $incidente) {
            $recorrido = $flujo->recorrer(HechosDelIncidente::de($incidente, $ahora));
            $decisiones = array_map(fn (array $decision) => ['campo' => $decision['campo']->value, 'cumple' => $decision['cumple']], $recorrido->decisiones);
            $clave = json_encode($decisiones, JSON_THROW_ON_ERROR);

            $caminos[$clave] ??= [
                'decisiones' => $decisiones,
                'acciones' => array_map(fn (Accion $accion) => $accion->tipo->value, $recorrido->acciones),
                'cantidad' => 0,
            ];
            $caminos[$clave]['cantidad']++;
            $evaluados++;

            if ($recorrido->acciones !== []) {
                $conAcciones++;

                if (count($ejemplos) < self::EJEMPLOS) {
                    $ejemplos[] = ['id' => $incidente->id, 'codigo' => CodigoIncidente::deId($incidente->id), 'titulo' => $incidente->titulo];
                }
            }
        }

        return [
            'dias' => self::DIAS,
            'evaluados' => $evaluados,
            'con_acciones' => $conAcciones,
            'caminos' => array_values($caminos),
            'ejemplos' => $ejemplos,
        ];
    }
}
