<?php

namespace App\Incidentes\Consultas;

use App\Identidad\Models\User;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\MensajeIncidente;
use App\Incidentes\Policies\IncidentePolicy;
use Illuminate\Contracts\Pagination\CursorPaginator;

class ConversacionDeIncidente
{
    public const POR_PAGINA = 20;

    /**
     * Mensajes del incidente del más reciente al más antiguo, por tramos, sin las notas internas si quien lee no
     * puede verlas. Como en un chat, primero se ve lo último y se piden los anteriores al subir: paginación por
     * cursor, que no cuenta ni salta filas.
     *
     * @return CursorPaginator<int, MensajeIncidente>
     */
    public function ejecutar(User $lector, Incidente $incidente): CursorPaginator
    {
        return MensajeIncidente::where('incidente_id', $incidente->id)
            ->visiblesPara(IncidentePolicy::alcanceDe($lector))
            ->with(['autor:id,name', 'adjuntos'])
            ->orderByDesc('id')
            ->cursorPaginate(self::POR_PAGINA);
    }
}
