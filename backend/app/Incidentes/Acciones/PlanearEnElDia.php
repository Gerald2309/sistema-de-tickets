<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\Excepciones\NoCabeEnElDia;
use App\Incidentes\Dominio\Jornada;
use App\Incidentes\Models\EntradaDelPlan;
use App\Incidentes\Models\Incidente;
use App\Incidentes\Models\NotaPersonal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PlanearEnElDia
{
    /**
     * Pone un incidente o una nota en la agenda de ese día, en el primer hueco desde `$desde`. Si ya estaba ese
     * día (o la nota tenía hora en otro), la mueve: no se duplica.
     *
     * @throws NoCabeEnElDia si desde esa hora ya no queda un hueco de esa duración.
     */
    public function ejecutar(User $usuario, Incidente|NotaPersonal $que, CarbonImmutable $fecha, int $desde, ?int $duracion = null): EntradaDelPlan
    {
        return DB::transaction(function () use ($usuario, $que, $fecha, $desde, $duracion) {
            $this->bloquearDia($usuario->id, $fecha);

            $entrada = $que instanceof Incidente
                ? EntradaDelPlan::de($usuario->id)->where('fecha', $fecha->toDateString())->where('incidente_id', $que->id)->first()
                : $que->entrada()->first();
            $entrada ??= new EntradaDelPlan([
                'usuario_id' => $usuario->id,
                'incidente_id' => $que instanceof Incidente ? $que->id : null,
                'nota_id' => $que instanceof NotaPersonal ? $que->id : null,
            ]);
            $duracion ??= $entrada->exists
                ? $entrada->duracion
                : ($que instanceof Incidente ? Jornada::DURACION_PREDETERMINADA : Jornada::DURACION_DE_NOTA);

            return $this->ubicar($entrada, $fecha, $desde, $duracion, exacto: false);
        });
    }

    /**
     * Mueve una entrada a otro día u hora (al primer hueco desde ahí) o le cambia la duración. Cambiar solo la
     * duración es exacto: si choca con lo siguiente, no cabe.
     *
     * @throws NoCabeEnElDia
     */
    public function reubicar(EntradaDelPlan $entrada, CarbonImmutable $fecha, int $desde, int $duracion, bool $exacto): EntradaDelPlan
    {
        return DB::transaction(function () use ($entrada, $fecha, $desde, $duracion, $exacto) {
            $this->bloquearDia($entrada->usuario_id, $fecha);

            // El mismo incidente va una vez por día: si ya estaba en el día de destino, queda solo el que se movió.
            if ($entrada->incidente_id !== null) {
                EntradaDelPlan::de($entrada->usuario_id)
                    ->where('fecha', $fecha->toDateString())
                    ->where('incidente_id', $entrada->incidente_id)
                    ->whereKeyNot($entrada->id)
                    ->delete();
            }

            return $this->ubicar($entrada, $fecha, $desde, $duracion, $exacto);
        });
    }

    /**
     * Bloquea la agenda de ese día de esa persona: dos pestañas que planean a la vez no se pisan el hueco.
     */
    private function bloquearDia(int $usuarioId, CarbonImmutable $fecha): void
    {
        EntradaDelPlan::de($usuarioId)->where('fecha', $fecha->toDateString())->lockForUpdate()->get(['id']);
    }

    private function ubicar(EntradaDelPlan $entrada, CarbonImmutable $fecha, int $desde, int $duracion, bool $exacto): EntradaDelPlan
    {
        $ocupados = array_values(EntradaDelPlan::de($entrada->usuario_id)
            ->where('fecha', $fecha->toDateString())
            ->when($entrada->exists, fn ($otras) => $otras->whereKeyNot($entrada->id))
            ->get(['inicio', 'duracion'])
            ->map(fn (EntradaDelPlan $otra) => [$otra->inicio, $otra->fin()])
            ->all());

        $inicio = $exacto
            ? (Jornada::cabe($ocupados, $desde, $duracion) ? $desde : null)
            : Jornada::huecoLibre($ocupados, $desde, $duracion);
        if ($inicio === null) {
            throw new NoCabeEnElDia;
        }

        $entrada->fill(['fecha' => $fecha->toDateString(), 'inicio' => $inicio, 'duracion' => $duracion])->save();

        return $entrada;
    }
}
