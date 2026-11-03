<?php

namespace App\Incidentes\Notificaciones;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

// Aviso de la campanita sobre un incidente, para una persona: queda guardado hasta que lo lee y le llega al
// instante por su canal privado. Por el canal no viaja el texto, solo que hay un aviso nuevo: el frontend
// lo pide a la API, como todo lo del tiempo real. Va por la cola y solo si la transacción se confirma.
final class AvisoDeIncidente extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $incidenteId, public readonly string $texto)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $persona): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array{incidente_id: int, texto: string}
     */
    public function toArray(object $persona): array
    {
        return ['incidente_id' => $this->incidenteId, 'texto' => $this->texto];
    }

    public function toBroadcast(object $persona): BroadcastMessage
    {
        return new BroadcastMessage([]);
    }

    public function broadcastType(): string
    {
        return 'aviso.nuevo';
    }
}
