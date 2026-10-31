<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Models\EntradaDelPlan;
use App\Incidentes\Policies\IncidentePolicy;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PlanDelPeriodo
{
    // Lo más largo que se pide de una vez: un mes con las semanas que asoman en su cuadrícula.
    public const DIAS_MAXIMOS = 42;

    /**
     * La agenda de esa persona entre dos días, en orden. Un incidente que ya no ve sale de su agenda.
     *
     * @return Collection<int, EntradaDelPlan>
     */
    public function ejecutar(User $usuario, CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        $alcance = IncidentePolicy::alcanceDe($usuario);

        return EntradaDelPlan::de($usuario->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->where(fn (Builder $entradas) => $entradas->whereNotNull('nota_id')
                ->orWhereHas('incidente', fn (Builder $incidentes) => $incidentes->visiblePara($alcance)))
            ->with($this->relaciones($usuario))
            ->orderBy('fecha')
            ->orderBy('inicio')
            ->get();
    }

    /**
     * Lo que se carga de cada entrada para mostrarla, también al devolver la que se acaba de planear.
     *
     * @return array<string, Closure>
     */
    public function relaciones(User $usuario): array
    {
        $alcance = IncidentePolicy::alcanceDe($usuario);

        return [
            'incidente' => fn ($incidentes) => $incidentes->paraElPlanDe($usuario->id),
            'nota.incidente' => fn ($incidentes) => $incidentes->withoutEagerLoads()->visiblePara($alcance)->select('incidentes.id'),
        ];
    }
}
