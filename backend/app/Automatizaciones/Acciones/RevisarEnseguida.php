<?php

namespace App\Automatizaciones\Acciones;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Models\Automatizacion;
use App\Automatizaciones\Trabajos\RevisarAutomatizacion;

// Una automatización por tiempo recién encendida o guardada no espera la próxima revisión del programador (hasta
// cinco minutos): se revisa ya, por la cola y solo si el cambio se confirma.
class RevisarEnseguida
{
    public function ejecutar(Automatizacion $automatizacion): void
    {
        if ($automatizacion->activa && $automatizacion->disparador !== Disparador::Creacion) {
            RevisarAutomatizacion::dispatch($automatizacion->id)->afterCommit();
        }
    }
}
