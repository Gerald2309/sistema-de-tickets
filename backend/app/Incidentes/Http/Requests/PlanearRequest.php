<?php

namespace App\Incidentes\Http\Requests;

use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\NotaPersonal;
use App\Incidentes\Policies\IncidentePolicy;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class PlanearRequest extends MiDiaRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['required', self::FECHA],
            'inicio' => ['required', ...self::reglasDeInicio()],
            'duracion' => ['sometimes', ...self::reglasDeDuracion()],
            'incidente_id' => ['bail', 'required_without:nota_id', 'prohibits:nota_id', 'integer', $this->incidentePlaneable(...)],
            'nota_id' => ['bail', 'required_without:incidente_id', 'integer', $this->notaPropia(...)],
        ];
    }

    // Se planea lo que se puede trabajar: un incidente sin resolver que la persona ve.
    private function incidentePlaneable(string $atributo, mixed $id, Closure $fallar): void
    {
        if (! $this->incidentes()->whereKey($id)->exists()) {
            $fallar(__('incidentes.solo_se_planea_lo_abierto'));
        }
    }

    private function notaPropia(string $atributo, mixed $id, Closure $fallar): void
    {
        if (! NotaPersonal::de($this->user()->id)->whereKey($id)->exists()) {
            $fallar(__('validation.exists', ['attribute' => $atributo]));
        }
    }

    public function que(): Incidente|NotaPersonal
    {
        return $this->has('incidente_id')
            ? $this->incidentes()->findOrFail((int) $this->validated('incidente_id'))
            : NotaPersonal::de($this->user()->id)->findOrFail((int) $this->validated('nota_id'));
    }

    public function fecha(): CarbonImmutable
    {
        return $this->dia('fecha');
    }

    public function inicio(): int
    {
        return (int) $this->validated('inicio');
    }

    public function duracion(): ?int
    {
        return $this->has('duracion') ? (int) $this->validated('duracion') : null;
    }

    /**
     * @return Builder<Incidente>
     */
    private function incidentes(): Builder
    {
        return Incidente::query()
            ->withoutEagerLoads()
            ->visiblePara(IncidentePolicy::alcanceDe($this->user()))
            ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen()));
    }
}
