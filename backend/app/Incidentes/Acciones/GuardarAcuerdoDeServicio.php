<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use App\Incidentes\Models\AcuerdoDeServicio;
use Illuminate\Support\Facades\Log;

class GuardarAcuerdoDeServicio
{
    /**
     * Rige para los incidentes que se creen desde ahora: los que ya existen conservan sus límites.
     *
     * @param  array<string, array{respuesta_minutos: int, resolucion_minutos: int}>  $plazos  ya validados
     */
    public function ejecutar(User $autor, Cliente $cliente, bool $horarioLaboral, array $plazos): AcuerdoDeServicio
    {
        $acuerdo = AcuerdoDeServicio::updateOrCreate(
            ['cliente_id' => $cliente->id],
            ['horario_laboral' => $horarioLaboral, 'plazos' => $plazos],
        );

        Log::info('Acuerdo de servicio guardado', ['usuario_id' => $autor->id, 'cliente_id' => $cliente->id]);

        return $acuerdo;
    }
}
