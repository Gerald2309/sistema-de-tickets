<?php

namespace App\Incidentes\Acciones;

use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Eventos\IncidentesCambiados;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

// Junta los cambios de incidentes de una petición y los avisa por WebSocket una vez confirmada la
// transacción, en un solo mensaje: un lote de cien cambios produce un aviso, no cien. Si la transacción
// se revierte, no se avisa nada. Si el servidor de WebSockets falla, el cambio ya está guardado: se
// registra el error y la petición responde igual (el frontend tiene su refresco de respaldo).
// Se registra con alcance de petición (AppServiceProvider): cada petición tiene su propia instancia.
class AvisarCambios
{
    /** @var array<int, true> */
    private array $ids = [];

    /** @var array<int, true> */
    private array $personas = [];

    private ?TipoDeCambio $tipo = null;

    /**
     * @param  int|null  ...$otrasPersonas  por ejemplo, quien deja de estar asignado
     */
    public function registrar(Incidente $incidente, TipoDeCambio $tipo, ?int ...$otrasPersonas): void
    {
        $this->ids[$incidente->id] = true;
        foreach ([$incidente->usuario_creador_id, $incidente->usuario_asignado_id, ...$otrasPersonas] as $persona) {
            if ($persona !== null) {
                $this->personas[$persona] = true;
            }
        }
        $this->tipo = $this->tipo?->combinadoCon($tipo) ?? $tipo;

        // Fuera de una transacción se ejecuta en el acto; dentro, al confirmarse la más externa. Registrarlo
        // varias veces es inocuo: la primera ejecución envía todo y las demás no encuentran nada pendiente.
        DB::afterCommit(fn () => $this->enviar());
    }

    private function enviar(): void
    {
        if ($this->tipo === null) {
            return;
        }

        $evento = new IncidentesCambiados(array_keys($this->ids), $this->tipo, array_keys($this->personas));
        $this->ids = $this->personas = [];
        $this->tipo = null;

        rescue(fn () => event($evento), report: true);
    }
}
