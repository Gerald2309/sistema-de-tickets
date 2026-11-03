<?php

namespace App\Incidentes\Eventos;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

// Se creó un incidente. Sale solo si la transacción se confirma: quien lo escuche siempre lo encuentra.
final readonly class IncidenteCreado implements ShouldDispatchAfterCommit
{
    public function __construct(public int $incidenteId) {}
}
