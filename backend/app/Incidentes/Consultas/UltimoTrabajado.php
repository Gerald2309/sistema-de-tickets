<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use App\Incidentes\Policies\IncidentePolicy;
use Carbon\CarbonInterface;

class UltimoTrabajado
{
    /**
     * El último incidente que esa persona tocó (un mensaje o un cambio), para «continuar donde quedó». Sale de
     * su propia actividad: no se guarda aparte. Null si no tocó ninguno o ya no lo ve.
     *
     * @return array{incidente: Incidente, momento: CarbonInterface, accion: 'mensaje'|'cambio'}|null
     */
    public function ejecutar(User $usuario): ?array
    {
        $cambio = HistorialIncidente::where('usuario_id', $usuario->id)->latest('id')->first(['incidente_id', 'created_at']);
        $mensaje = MensajeIncidente::where('usuario_id', $usuario->id)->latest('id')->first(['incidente_id', 'created_at']);

        $ultimo = match (true) {
            $cambio === null => $mensaje,
            $mensaje === null => $cambio,
            default => $mensaje->created_at->gte($cambio->created_at) ? $mensaje : $cambio,
        };
        if ($ultimo === null) {
            return null;
        }

        $incidente = Incidente::paraElPlanDe($usuario->id)
            ->visiblePara(IncidentePolicy::alcanceDe($usuario))
            ->find($ultimo->incidente_id);

        return $incidente === null ? null : [
            'incidente' => $incidente,
            'momento' => $ultimo->created_at,
            'accion' => $ultimo instanceof MensajeIncidente ? 'mensaje' : 'cambio',
        ];
    }
}
