<?php

namespace App\Incidentes\Http\Requests;

use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class CrearIncidenteRequest extends IncidenteRequest
{
    // Cabecera opcional: el cliente genera un UUID por formulario y lo repite si reintenta el envío.
    public const CABECERA_DE_IDEMPOTENCIA = 'Idempotency-Key';

    public function authorize(): bool
    {
        return $this->input('usuario_asignado_id') === null || $this->alcance()->puedeAsignarAlCrear();
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // Sin fecha de vencimiento, la calcula el acuerdo de servicio de la empresa.
        return ['fecha_vencimiento' => ['sometimes', 'date_format:'.self::FORMATOS_FECHA]] + $this->reglasComunes('required');
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            $clave = $this->header(self::CABECERA_DE_IDEMPOTENCIA);
            if ($clave !== null && ! Str::isUuid($clave)) {
                $validador->errors()->add(self::CABECERA_DE_IDEMPOTENCIA, trans('incidentes.clave_de_idempotencia_invalida'));
            }
        }];
    }

    public function claveDeIdempotencia(): ?string
    {
        return $this->header(self::CABECERA_DE_IDEMPOTENCIA);
    }
}
