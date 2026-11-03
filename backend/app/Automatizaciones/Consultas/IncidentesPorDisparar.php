<?php

namespace App\Automatizaciones\Consultas;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\Disparo;
use App\Automatizaciones\Dominio\PlazoDelSla;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LogicException;

// Los incidentes a los que ya les llegó el momento de una automatización por tiempo y que todavía no la
// recibieron. El instante se recibe como argumento: la consulta no depende del reloj.
class IncidentesPorDisparar
{
    /**
     * Los más antiguos primero, como máximo `$maximo`: los demás quedan para la próxima revisión.
     *
     * @param  int|null  $automatizacionId  para dejar fuera los que ya la recibieron; null si aún no se guarda (una prueba)
     * @return list<int>
     */
    public function ids(Disparo $disparo, ?int $automatizacionId, CarbonImmutable $ahora, int $maximo): array
    {
        $consulta = Incidente::query()
            ->withoutEagerLoads()
            ->when($automatizacionId !== null, fn (Builder $pendientes) => $pendientes->whereNotExists(
                fn (QueryBuilder $ejecuciones) => $ejecuciones->from('ejecuciones_de_automatizacion')
                    ->whereColumn('ejecuciones_de_automatizacion.incidente_id', 'incidentes.id')
                    ->where('ejecuciones_de_automatizacion.automatizacion_id', $automatizacionId),
            ));

        $this->llegoElMomento($consulta, $disparo, $ahora);

        /** @var list<int> */
        return $consulta->orderBy('incidentes.id')->limit($maximo)->pluck('incidentes.id')->all();
    }

    /**
     * @param  Builder<Incidente>  $consulta
     */
    private function llegoElMomento(Builder $consulta, Disparo $disparo, CarbonImmutable $ahora): void
    {
        match ($disparo->disparador) {
            Disparador::SinRespuesta => $this->sinAtender($consulta, PlazoDelSla::Respuesta)
                ->where('created_at', '<=', $ahora->subMinutes((int) $disparo->esperaMinutos)),
            Disparador::ResueltoSinConfirmar => $consulta->where('estado', Estado::Resuelto)
                ->where('resuelto_en', '<=', $ahora->subMinutes((int) $disparo->esperaMinutos)),
            Disparador::SlaVencido => $this->sinAtender($consulta, $this->plazo($disparo))
                ->where(self::limite($this->plazo($disparo)), '<', $ahora),
            Disparador::SlaPorVencer => $this->porVencer($consulta, $this->plazo($disparo), (int) $disparo->porcentaje, $ahora),
            Disparador::Creacion => throw new LogicException('La creación no es un disparador por tiempo.'),
        };
    }

    /**
     * Sin vencer todavía, pero ya pasó ese porcentaje del tiempo entre la creación y el límite.
     *
     * @param  Builder<Incidente>  $consulta
     */
    private function porVencer(Builder $consulta, PlazoDelSla $plazo, int $porcentaje, CarbonImmutable $ahora): void
    {
        $limite = self::limite($plazo);

        $this->sinAtender($consulta, $plazo)
            ->where($limite, '>', $ahora)
            ->whereRaw("TIMESTAMPDIFF(SECOND, created_at, ?) * 100 >= TIMESTAMPDIFF(SECOND, created_at, {$limite}) * ?", [$ahora, $porcentaje]);
    }

    /**
     * Los que siguen esperando lo que ese plazo mide: la primera respuesta o la resolución.
     *
     * @param  Builder<Incidente>  $consulta
     * @return Builder<Incidente>
     */
    private function sinAtender(Builder $consulta, PlazoDelSla $plazo): Builder
    {
        $consulta->whereIn('estado', Vencimiento::estadosQueVencen());

        return $plazo === PlazoDelSla::Respuesta ? $consulta->whereNull('primera_respuesta_en') : $consulta;
    }

    /**
     * La columna con el límite de ese plazo. Un incidente sin límite no vence por él: las comparaciones con
     * NULL no lo incluyen.
     *
     * @return 'vence_respuesta_en'|'fecha_vencimiento'
     */
    private static function limite(PlazoDelSla $plazo): string
    {
        return $plazo === PlazoDelSla::Respuesta ? 'vence_respuesta_en' : 'fecha_vencimiento';
    }

    private function plazo(Disparo $disparo): PlazoDelSla
    {
        return $disparo->plazo ?? throw new LogicException('Ese disparador siempre lleva un plazo.');
    }
}
