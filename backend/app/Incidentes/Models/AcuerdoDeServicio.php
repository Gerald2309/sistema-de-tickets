<?php

namespace App\Incidentes\Models;

use App\Incidentes\Dominio\PlazosDeServicio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property bool $horario_laboral
 * @property array<string, array{respuesta_minutos: int, resolucion_minutos: int}> $plazos
 */
#[Fillable(['cliente_id', 'horario_laboral', 'plazos'])]
class AcuerdoDeServicio extends Model
{
    protected $table = 'acuerdos_de_servicio';

    public function aPlazos(): PlazosDeServicio
    {
        return new PlazosDeServicio($this->horario_laboral, $this->plazos);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'horario_laboral' => 'boolean',
            'plazos' => 'array',
        ];
    }
}
