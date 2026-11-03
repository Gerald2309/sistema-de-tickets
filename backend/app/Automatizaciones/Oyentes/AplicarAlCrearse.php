<?php

namespace App\Automatizaciones\Oyentes;

use App\Automatizaciones\Acciones\AplicarAutomatizaciones;
use App\Automatizaciones\Dominio\Disparador;
use App\Incidentes\Eventos\IncidenteCreado;
use Illuminate\Contracts\Queue\ShouldQueue;

// Por la cola: quien crea el incidente no espera a que se apliquen las automatizaciones.
class AplicarAlCrearse implements ShouldQueue
{
    public function __construct(private readonly AplicarAutomatizaciones $aplicarAutomatizaciones) {}

    public function handle(IncidenteCreado $evento): void
    {
        $this->aplicarAutomatizaciones->ejecutar($evento->incidenteId, Disparador::Creacion);
    }
}
