<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class MarcarBloqueo
{
    /**
     * Pone o cambia la marca personal de «bloqueado» con su motivo; sin motivo, la quita. Es solo de esa
     * persona: no cambia el incidente ni pausa su vencimiento.
     */
    public function ejecutar(User $usuario, Incidente $incidente, ?string $motivo): void
    {
        $clave = ['usuario_id' => $usuario->id, 'incidente_id' => $incidente->id];

        if ($motivo === null) {
            DB::table('bloqueos_personales')->where($clave)->delete();

            return;
        }

        $ahora = now();
        DB::table('bloqueos_personales')->upsert(
            $clave + ['motivo' => $motivo, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['usuario_id', 'incidente_id'],
            ['motivo', 'updated_at'],
        );
    }
}
