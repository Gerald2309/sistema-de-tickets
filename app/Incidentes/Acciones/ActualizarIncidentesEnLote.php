<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CodigoIncidente;
use App\Incidentes\Dominio\Excepciones\TransicionInvalida;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarIncidentesEnLote
{
    public function __construct(private readonly ActualizarIncidente $actualizarIncidente) {}

    /**
     * Aplica los mismos cambios a varios incidentes, todos o ninguno: si uno no puede cambiar (una
     * transición no permitida, falta la solución), no se modifica ninguno y el error dice cuál fue.
     *
     * @param  Collection<int, Incidente>  $incidentes  los que la persona ve, de menor a mayor id
     * @param  array<string, mixed>  $cambios  ya validados
     * @return Collection<int, Incidente>
     *
     * @throws AuthorizationException si no puede hacer esos cambios en alguno de los incidentes.
     * @throws ValidationException
     */
    public function ejecutar(User $autor, Collection $incidentes, array $cambios): Collection
    {
        $alcance = IncidentePolicy::alcanceDe($autor);

        foreach ($incidentes as $incidente) {
            if (! $alcance->puedeModificar(array_keys($cambios), $incidente->usuario_creador_id)) {
                throw new AuthorizationException;
            }
        }

        return DB::transaction(fn () => $incidentes->map(fn (Incidente $incidente) => $this->actualizar($autor, $incidente, $cambios)));
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function actualizar(User $autor, Incidente $incidente, array $cambios): Incidente
    {
        $codigo = CodigoIncidente::deId($incidente->id);

        try {
            return $this->actualizarIncidente->ejecutar($autor, $incidente, $cambios);
        } catch (TransicionInvalida $excepcion) {
            throw ValidationException::withMessages(['ids' => ["{$codigo}: {$excepcion->getMessage()}"]]);
        } catch (ValidationException $excepcion) {
            throw ValidationException::withMessages(['cambios' => array_map(
                fn (string $mensaje) => "{$codigo}: {$mensaje}",
                array_merge(...array_values($excepcion->errors())),
            )]);
        }
    }
}
