<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Consultas\PlanDelPeriodo;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

class VerPlanRequest extends MiDiaRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'desde' => ['required', self::FECHA],
            'hasta' => ['required', self::FECHA, 'after_or_equal:desde'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validador) {
            if ($validador->errors()->isEmpty() && $this->desde()->diffInDays($this->hasta()) >= PlanDelPeriodo::DIAS_MAXIMOS) {
                $validador->errors()->add('hasta', __('incidentes.periodo_del_plan_largo', ['dias' => PlanDelPeriodo::DIAS_MAXIMOS]));
            }
        }];
    }

    public function desde(): CarbonImmutable
    {
        return $this->dia('desde');
    }

    public function hasta(): CarbonImmutable
    {
        return $this->dia('hasta');
    }
}
