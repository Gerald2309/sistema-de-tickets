<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Consultas\PorPlanear;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\NoCabeEnElDia;
use App\Incidentes\Dominio\Jornada;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\EntradaDelPlan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SugerirDia
{
    public function __construct(
        private readonly PorPlanear $porPlanear,
        private readonly PlanearEnElDia $planear,
    ) {}

    /**
     * Llena el día hasta lo habitual con lo más urgente de la bandeja (sin lo bloqueado), en los huecos libres
     * desde `$desde`. Para cuando ya no cabe más. Todo o nada: una sola transacción.
     *
     * @return list<EntradaDelPlan> lo que agregó
     */
    public function ejecutar(User $usuario, CarbonImmutable $fecha, int $desde): array
    {
        return DB::transaction(function () use ($usuario, $fecha, $desde) {
            $yaPlaneados = EntradaDelPlan::de($usuario->id)
                ->where('fecha', $fecha->toDateString())
                ->whereHas('incidente', fn (Builder $incidentes) => $incidentes
                    ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen())))
                ->count();

            $agregados = [];
            foreach ($this->porPlanear->sugeribles($usuario, max(Jornada::CAPACIDAD_HABITUAL - $yaPlaneados, 0)) as $incidente) {
                try {
                    $agregados[] = $this->planear->ejecutar($usuario, $incidente, $fecha, $desde);
                } catch (NoCabeEnElDia) {
                    break;
                }
            }

            return $agregados;
        });
    }
}
