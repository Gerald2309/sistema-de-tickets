<?php

namespace App\Incidentes\Http\Requests;

use App\Http\Requests\ConClaveDeIdempotencia;
use Illuminate\Validation\Validator;

class CrearIncidenteRequest extends IncidenteRequest
{
    use ConClaveDeIdempotencia;

    public function authorize(): bool
    {
        return $this->alcance()->puedePedirEstosPlazos($this->all())
            && ($this->input('usuario_asignado_id') === null || $this->alcance()->puedeAsignarAlCrear());
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // Sin fecha de vencimiento, la calcula el acuerdo de servicio de la empresa.
        return [
            'fecha_vencimiento' => ['sometimes', 'date_format:'.self::FORMATOS_FECHA],
            // Se dice al reportar y solo informa a soporte: no mueve los plazos del acuerdo.
            'fecha_requerida' => ['sometimes', 'nullable', 'date_format:'.self::FORMATOS_FECHA, 'after:now'],
        ] + $this->reglasComunes('required');
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [$this->validarClaveDeIdempotencia()];
    }
}
