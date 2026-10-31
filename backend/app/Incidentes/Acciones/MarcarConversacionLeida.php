<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Support\Facades\DB;

class MarcarConversacionLeida
{
    /**
     * Deja leída la conversación hasta su último mensaje visible para esa persona. Repetirlo no cambia nada.
     */
    public function ejecutar(User $lector, Incidente $incidente): void
    {
        $ultimo = MensajeIncidente::where('incidente_id', $incidente->id)
            ->visiblesPara(IncidentePolicy::alcanceDe($lector))
            ->max('id');

        if ($ultimo === null) {
            return;
        }

        DB::table('lecturas_conversacion')->upsert(
            ['incidente_id' => $incidente->id, 'usuario_id' => $lector->id, 'ultimo_mensaje_leido_id' => $ultimo],
            ['incidente_id', 'usuario_id'],
            ['ultimo_mensaje_leido_id'],
        );
    }
}
