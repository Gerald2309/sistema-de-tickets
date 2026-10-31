<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Models\NotaPersonal;
use App\Incidentes\Policies\IncidentePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotasDeLaPizarra
{
    public const MAXIMO = 50;

    // Lo hecho se queda unos días a la vista (tachado) y luego sale de la pizarra.
    public const DIAS_DE_LO_HECHO = 7;

    /**
     * Las notas de esa persona que no tienen hora desde hoy: las sueltas y las que quedaron de días anteriores.
     * Primero lo pendiente y lo más reciente.
     *
     * @return Collection<int, NotaPersonal>
     */
    public function ejecutar(User $usuario, CarbonImmutable $hoy): Collection
    {
        $alcance = IncidentePolicy::alcanceDe($usuario);

        return NotaPersonal::de($usuario->id)
            ->whereDoesntHave('entrada', fn (Builder $entradas) => $entradas->where('fecha', '>=', $hoy->toDateString()))
            ->where(fn (Builder $notas) => $notas->where('hecha', false)
                ->orWhere('updated_at', '>=', $hoy->subDays(self::DIAS_DE_LO_HECHO)))
            ->with(['incidente' => fn ($incidentes) => $incidentes->withoutEagerLoads()->visiblePara($alcance)->select('incidentes.id')])
            ->orderBy('hecha')
            ->orderByDesc('id')
            ->limit(self::MAXIMO)
            ->get();
    }
}
