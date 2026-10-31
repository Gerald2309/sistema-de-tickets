<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Excepciones\NoSePuedeTomar;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class TomarIncidente
{
    public function __construct(private readonly ActualizarIncidente $actualizarIncidente) {}

    /**
     * Asigna el incidente a quien lo toma. Se comprueba con el incidente bloqueado: si dos personas lo
     * toman a la vez, una lo consigue y la otra recibe el motivo.
     *
     * @throws NoSePuedeTomar si ya tiene asignado o está cerrado.
     */
    public function ejecutar(User $persona, Incidente $incidente): Incidente
    {
        return DB::transaction(function () use ($persona, $incidente) {
            $actual = Incidente::lockForUpdate()->findOrFail($incidente->id);

            if ($actual->estado->esFinal()) {
                throw NoSePuedeTomar::estaCerrado();
            }

            if (! $actual->participantes()->sinAsignar()) {
                throw NoSePuedeTomar::yaTieneAsignado();
            }

            return $this->actualizarIncidente->ejecutar($persona, $actual, ['usuario_asignado_id' => $persona->id]);
        });
    }
}
