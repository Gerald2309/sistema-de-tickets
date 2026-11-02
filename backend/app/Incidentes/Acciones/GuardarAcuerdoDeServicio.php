<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Consultas\PlazosDeLaEmpresa;
use App\Incidentes\Eventos\AcuerdoDeServicioCambiado;
use App\Incidentes\Models\AcuerdoDeServicio;
use Illuminate\Support\Facades\DB;

class GuardarAcuerdoDeServicio
{
    public function __construct(private readonly PlazosDeLaEmpresa $plazosDeLaEmpresa) {}

    /**
     * Rige para los incidentes que se creen desde ahora: los que ya existen conservan sus límites. Si algo cambió,
     * lo avisa para que quede en la auditoría junto con lo que regía antes.
     *
     * @param  array<string, array{respuesta_minutos: int, resolucion_minutos: int}>  $plazos  ya validados
     */
    public function ejecutar(User $autor, Cliente $cliente, bool $horarioLaboral, array $plazos, ?string $ip): AcuerdoDeServicio
    {
        return DB::transaction(function () use ($autor, $cliente, $horarioLaboral, $plazos, $ip) {
            $antes = $this->plazosDeLaEmpresa->de($cliente->id);
            $acuerdo = AcuerdoDeServicio::updateOrCreate(
                ['cliente_id' => $cliente->id],
                ['horario_laboral' => $horarioLaboral, 'plazos' => $plazos],
            );
            $cambios = $antes->cambiosHacia($acuerdo->aPlazos());

            if ($cambios !== []) {
                event(new AcuerdoDeServicioCambiado($autor->id, $cliente->nombre, $cambios, $ip));
            }

            return $acuerdo;
        });
    }
}
