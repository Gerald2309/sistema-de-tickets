<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\NoSePuedeConfirmar;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class ConfirmarSolucion
{
    public function __construct(
        private readonly ActualizarIncidente $actualizarIncidente,
        private readonly EscribirMensaje $escribirMensaje,
    ) {}

    /**
     * Quien reportó el incidente dice si la solución le sirvió. Si sirvió, el incidente se cierra con su
     * calificación de la atención; si no, vuelve a quien lo atiende con el motivo en la conversación, para
     * que lo lea sin buscarlo.
     *
     * @param  string|null  $motivo  obligatorio si no quedó solucionado (lo valida la petición)
     * @param  int|null  $calificacion  de 1 a 5, opcional y solo si quedó solucionado
     *
     * @throws NoSePuedeConfirmar si el incidente no está resuelto.
     */
    public function ejecutar(User $solicitante, Incidente $incidente, bool $solucionado, ?string $motivo, ?int $calificacion): Incidente
    {
        return DB::transaction(function () use ($solicitante, $incidente, $solucionado, $motivo, $calificacion) {
            // Con el incidente bloqueado: soporte podría estar cambiándolo a la vez.
            $actual = Incidente::lockForUpdate()->findOrFail($incidente->id);

            if ($actual->estado !== Estado::Resuelto) {
                throw new NoSePuedeConfirmar;
            }

            if ($solucionado) {
                return $this->actualizarIncidente->ejecutar($solicitante, $actual, [
                    'estado' => Estado::Cerrado->value,
                    'calificacion' => $calificacion,
                ]);
            }

            $reabierto = $this->actualizarIncidente->ejecutar($solicitante, $actual, ['estado' => Estado::EnProgreso->value]);
            $this->escribirMensaje->ejecutar($solicitante, $reabierto, $motivo, false, []);

            return $reabierto;
        });
    }
}
