<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Dominio\PlazosDeServicio;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Models\AcuerdoDeServicio;
use Carbon\CarbonImmutable;

class PlazosDeLaEmpresa
{
    // Sin empresa (personal interno) o sin acuerdo propio rigen los predeterminados.
    public function de(?int $clienteId): PlazosDeServicio
    {
        $acuerdo = $clienteId === null ? null : AcuerdoDeServicio::query()->where('cliente_id', $clienteId)->first();

        return $acuerdo?->aPlazos() ?? PlazosDeServicio::predeterminados();
    }

    /**
     * Los límites en UTC, listos para guardar. El horario laboral se calcula en la zona de la operación.
     *
     * @return array{respuesta: CarbonImmutable, resolucion: CarbonImmutable}
     */
    public function limites(?int $clienteId, Prioridad $prioridad, CarbonImmutable $inicio): array
    {
        $enLaZona = $inicio->setTimezone((string) config('incidentes.acuerdos.zona_horaria'));

        return array_map(
            fn (CarbonImmutable $limite) => $limite->utc(),
            $this->de($clienteId)->limitesDesde($enLaZona, $prioridad),
        );
    }
}
