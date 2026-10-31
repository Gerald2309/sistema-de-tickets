<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\FiltrosDeIncidentes;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;

class IncidentesParaExportar
{
    private const TAMANO_DE_LOTE = 1000;

    public function __construct(private readonly ListarIncidentes $listarIncidentes) {}

    /**
     * Los mismos incidentes que mostraría el listado con esos filtros, leídos por lotes para no cargar
     * todo en memoria. Si son más del máximo configurado se rechaza antes de empezar: una descarga a
     * medias no se puede corregir.
     *
     * @return LazyCollection<int, Incidente>
     *
     * @throws ValidationException
     */
    public function ejecutar(User $actor, FiltrosDeIncidentes $filtros): LazyCollection
    {
        $consulta = $this->listarIncidentes->consulta($actor, $filtros);
        $maximo = (int) config('incidentes.exportacion.maximo');

        // ¿Existe un incidente más allá del máximo? Más barato que contar todos.
        if ($consulta->clone()->offset($maximo)->limit(1)->exists()) {
            throw ValidationException::withMessages(['exportacion' => [trans('incidentes.exportacion_demasiado_grande', ['maximo' => number_format($maximo, 0, ',', '.')])]]);
        }

        return $consulta->lazy(self::TAMANO_DE_LOTE);
    }
}
