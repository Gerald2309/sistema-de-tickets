<?php

namespace App\Incidentes\Eventos;

use App\Incidentes\Dominio\TipoDeCambio;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

// Aviso de tiempo real (F-12): solo dice qué incidentes cambiaron, nunca sus datos. Quien lo recibe los
// vuelve a pedir a la API con su propio token, y la API decide qué puede ver. Va por la cola: la petición
// que cambió el incidente responde sin esperar a Reverb, y si Reverb no está, el aviso se reintenta.
final class IncidentesCambiados implements ShouldBroadcast
{
    /**
     * @param  list<int>  $ids
     * @param  list<int>  $personas  creadores y asignados afectados: cada una recibe el aviso en su canal
     */
    public function __construct(
        public readonly array $ids,
        public readonly TipoDeCambio $tipo,
        public readonly array $personas,
    ) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('incidentes'),
            ...array_map(fn (int $persona) => new PrivateChannel("usuario.{$persona}"), $this->personas),
        ];
    }

    public function broadcastAs(): string
    {
        return 'incidentes.cambiados';
    }

    /**
     * @return array{ids: list<int>, tipo: string}
     */
    public function broadcastWith(): array
    {
        return ['ids' => $this->ids, 'tipo' => $this->tipo->value];
    }
}
