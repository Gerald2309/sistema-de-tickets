<?php

namespace App\Automatizaciones\Trabajos;

use App\Automatizaciones\Acciones\AplicarAutomatizaciones;
use App\Automatizaciones\Consultas\IncidentesPorDisparar;
use App\Automatizaciones\Models\Automatizacion;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Date;

// Aplica una automatización por tiempo a los incidentes a los que ya les llegó el momento. Atiende un lote
// por revisión, para no ocupar la cola de golpe; los demás quedan para la siguiente. Una sola revisión de
// cada automatización a la vez: si la anterior sigue en la cola, no se encola otra.
class RevisarAutomatizacion implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const LOTE = 200;

    public function __construct(public readonly int $automatizacionId) {}

    public function uniqueId(): string
    {
        return (string) $this->automatizacionId;
    }

    public function handle(IncidentesPorDisparar $incidentesPorDisparar, AplicarAutomatizaciones $aplicarAutomatizaciones): void
    {
        // Se relee: entre la revisión y este trabajo pudieron apagarla o borrarla.
        $automatizacion = Automatizacion::query()->with('autor')->find($this->automatizacionId);

        if ($automatizacion === null || ! $automatizacion->activa) {
            return;
        }

        $ids = $incidentesPorDisparar->ids($automatizacion->disparo(), $automatizacion->id, Date::now()->toImmutable(), self::LOTE);

        foreach ($ids as $incidenteId) {
            $aplicarAutomatizaciones->aplicarA($automatizacion, $incidenteId);
        }
    }
}
