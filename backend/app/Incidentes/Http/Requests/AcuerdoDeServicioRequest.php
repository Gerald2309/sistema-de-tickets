<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\Prioridad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AcuerdoDeServicioRequest extends FormRequest
{
    // Un año: más que eso no es un plazo sino un error de digitación.
    private const MAXIMO_MINUTOS = 525_600;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $reglas = ['horario_laboral' => ['required', 'boolean']];

        foreach (Prioridad::cases() as $prioridad) {
            foreach (['respuesta_minutos', 'resolucion_minutos'] as $campo) {
                $reglas["plazos.{$prioridad->value}.{$campo}"] = ['required', 'integer', 'between:1,'.self::MAXIMO_MINUTOS];
            }
        }

        return $reglas;
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador): void {
            if ($validador->errors()->isNotEmpty()) {
                return;
            }

            foreach ($this->plazos() as $prioridad => $plazo) {
                if ($plazo['respuesta_minutos'] > $plazo['resolucion_minutos']) {
                    $validador->errors()->add("plazos.{$prioridad}.respuesta_minutos", trans('incidentes.respuesta_mayor_que_resolucion'));
                }
            }
        }];
    }

    /**
     * @return array<string, array{respuesta_minutos: int, resolucion_minutos: int}>
     */
    public function plazos(): array
    {
        $plazos = [];

        foreach (Prioridad::cases() as $prioridad) {
            $plazos[$prioridad->value] = [
                'respuesta_minutos' => $this->integer("plazos.{$prioridad->value}.respuesta_minutos"),
                'resolucion_minutos' => $this->integer("plazos.{$prioridad->value}.resolucion_minutos"),
            ];
        }

        return $plazos;
    }
}
