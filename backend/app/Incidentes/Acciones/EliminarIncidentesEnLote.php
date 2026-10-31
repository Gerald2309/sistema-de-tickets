<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EliminarIncidentesEnLote
{
    public function __construct(private readonly EliminarIncidente $eliminarIncidente) {}

    /**
     * Borrado lógico de varios incidentes, todos o ninguno.
     *
     * @param  Collection<int, Incidente>  $incidentes  los que la persona ve, de menor a mayor id
     *
     * @throws AuthorizationException si no puede borrar alguno.
     */
    public function ejecutar(User $autor, Collection $incidentes): void
    {
        $alcance = IncidentePolicy::alcanceDe($autor);

        foreach ($incidentes as $incidente) {
            if (! $alcance->puedeBorrar($incidente->usuario_creador_id)) {
                throw new AuthorizationException;
            }
        }

        DB::transaction(fn () => $incidentes->each(fn (Incidente $incidente) => $this->eliminarIncidente->ejecutar($autor, $incidente)));
    }
}
