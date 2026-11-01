<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Dominio\AlcanceIncidentes;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\IndicadoresDelPanel;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\HistorialIncidente;
use App\Incidentes\Models\Incidente;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sobre lo que la persona puede ver y sin hidratar modelos: conteos agrupados en SQL y, para las medianas,
 * solo la duración de cada incidente del periodo. Una transacción para leer la misma instantánea.
 */
class MedirIndicadores
{
    /**
     * @param  int  $desfase  minutos de la zona horaria de la persona respecto de UTC: los días de la tendencia son los suyos
     */
    public function ejecutar(AlcanceIncidentes $alcance, CarbonImmutable $ahora, int $desfase): IndicadoresDelPanel
    {
        return DB::transaction(function () use ($alcance, $ahora, $desfase) {
            $desde = $ahora->subDays(IndicadoresDelPanel::DIAS_DE_RESOLUCION);
            $resueltos = $this->resueltosEntre($alcance, $desde, $ahora);
            $anteriores = $this->resueltosEntre($alcance, $desde->subDays(IndicadoresDelPanel::DIAS_DE_RESOLUCION), $desde);

            return new IndicadoresDelPanel(
                espera: $this->espera($alcance, $ahora),
                tendencia: $this->tendencia($alcance, $ahora, $desfase),
                medianaDeResolucion: IndicadoresDelPanel::mediana(array_column($resueltos, 'segundos')),
                medianaAnterior: IndicadoresDelPanel::mediana(array_column($anteriores, 'segundos')),
                medianaDelPlazo: IndicadoresDelPanel::mediana(array_column($resueltos, 'plazo')),
                aTiempo: IndicadoresDelPanel::proporcion(count(array_filter(array_column($resueltos, 'a_tiempo'))), count($resueltos)),
                porPrioridad: $this->porPrioridad($resueltos),
                medianaHastaAtender: $this->medianaHastaAtender($alcance, $desde),
                respuestaATiempo: $this->respuestaATiempo($alcance, $desde),
                devueltos: $this->devueltos($alcance, $desde),
            );
        });
    }

    /**
     * @return Builder<Incidente>
     */
    private function visibles(AlcanceIncidentes $alcance): Builder
    {
        return Incidente::query()->withoutEagerLoads()->visiblePara($alcance);
    }

    /**
     * @return list<int>
     */
    private function espera(AlcanceIncidentes $alcance, CarbonImmutable $ahora): array
    {
        // Cortes del más reciente al más antiguo: cada tramo va de su corte (incluido) al del tramo anterior.
        $cortes = array_map(fn (int $dias) => $ahora->subDays($dias), IndicadoresDelPanel::TRAMOS_DE_ESPERA);
        $columnas = [];
        $valores = [];

        foreach (range(0, count($cortes)) as $i) {
            [$desde, $hasta] = [$cortes[$i] ?? null, $cortes[$i - 1] ?? null];
            $condiciones = [];
            if ($desde !== null) {
                $condiciones[] = 'created_at >= ?';
                $valores[] = $desde;
            }
            if ($hasta !== null) {
                $condiciones[] = 'created_at < ?';
                $valores[] = $hasta;
            }
            $columnas[] = 'count(case when '.implode(' and ', $condiciones)." then 1 end) as tramo{$i}";
        }

        $fila = $this->visibles($alcance)->toBase()
            ->whereIn('estado', array_column(Vencimiento::estadosQueVencen(), 'value'))
            ->selectRaw(implode(', ', $columnas), $valores)
            ->first();

        return array_map(fn (int $i) => (int) ($fila->{"tramo{$i}"} ?? 0), range(0, count($cortes)));
    }

    /**
     * @return list<array{fecha: string, entraron: int, resueltos: int}>
     */
    private function tendencia(AlcanceIncidentes $alcance, CarbonImmutable $ahora, int $desfase): array
    {
        $hoy = $ahora->addMinutes($desfase)->startOfDay();
        $primerDia = $hoy->subDays(IndicadoresDelPanel::DIAS_DE_TENDENCIA - 1);
        // El primer día local empieza en UTC $desfase minutos antes (o después) de su medianoche.
        $inicio = $primerDia->subMinutes($desfase);

        $entraron = $this->porDia($alcance, 'created_at', $inicio, $desfase);
        $resueltos = $this->porDia($alcance, 'resuelto_en', $inicio, $desfase);

        return array_map(function (int $n) use ($primerDia, $entraron, $resueltos) {
            $fecha = $primerDia->addDays($n)->toDateString();

            return ['fecha' => $fecha, 'entraron' => (int) ($entraron[$fecha] ?? 0), 'resueltos' => (int) ($resueltos[$fecha] ?? 0)];
        }, range(0, IndicadoresDelPanel::DIAS_DE_TENDENCIA - 1));
    }

    /**
     * @param  'created_at'|'resuelto_en'  $columna
     * @return Collection<string, mixed> cantidad por día local (Y-m-d)
     */
    private function porDia(AlcanceIncidentes $alcance, string $columna, CarbonImmutable $inicio, int $desfase): Collection
    {
        return $this->visibles($alcance)->toBase()
            ->selectRaw(match ($columna) {
                'created_at' => 'date(date_add(created_at, interval ? minute)) as dia, count(*) as cantidad',
                'resuelto_en' => 'date(date_add(resuelto_en, interval ? minute)) as dia, count(*) as cantidad',
            }, [$desfase])
            ->where($columna, '>=', $inicio)
            ->groupBy('dia')
            ->pluck('cantidad', 'dia');
    }

    /**
     * @return list<array{prioridad: string, segundos: int, plazo: int, a_tiempo: bool}>
     */
    private function resueltosEntre(AlcanceIncidentes $alcance, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        return array_values($this->visibles($alcance)->toBase()
            ->selectRaw('prioridad, timestampdiff(second, created_at, resuelto_en) as segundos, timestampdiff(second, created_at, fecha_vencimiento) as plazo, resuelto_en <= fecha_vencimiento as a_tiempo')
            ->where('resuelto_en', '>=', $desde)
            ->where('resuelto_en', '<', $hasta)
            ->get()
            ->map(fn (object $fila) => ['prioridad' => (string) $fila->prioridad, 'segundos' => (int) $fila->segundos, 'plazo' => (int) $fila->plazo, 'a_tiempo' => (bool) $fila->a_tiempo])
            ->all());
    }

    /**
     * @param  list<array{prioridad: string, segundos: int, plazo: int, a_tiempo: bool}>  $resueltos
     * @return array<string, array{mediana: int|null, plazo: int|null, a_tiempo: float|null, cantidad: int}>
     */
    private function porPrioridad(array $resueltos): array
    {
        $resultado = [];

        foreach (Prioridad::cases() as $prioridad) {
            $deEsta = array_values(array_filter($resueltos, fn (array $fila) => $fila['prioridad'] === $prioridad->value));
            $resultado[$prioridad->value] = [
                'mediana' => IndicadoresDelPanel::mediana(array_column($deEsta, 'segundos')),
                'plazo' => IndicadoresDelPanel::mediana(array_column($deEsta, 'plazo')),
                'a_tiempo' => IndicadoresDelPanel::proporcion(count(array_filter(array_column($deEsta, 'a_tiempo'))), count($deEsta)),
                'cantidad' => count($deEsta),
            ];
        }

        return $resultado;
    }

    // De las primeras respuestas del periodo, qué parte llegó dentro de su límite.
    private function respuestaATiempo(AlcanceIncidentes $alcance, CarbonImmutable $desde): ?float
    {
        $respuestas = $this->visibles($alcance)->toBase()
            ->where('primera_respuesta_en', '>=', $desde)
            ->whereNotNull('vence_respuesta_en')
            ->selectRaw('count(*) as total, count(case when primera_respuesta_en <= vence_respuesta_en then 1 end) as a_tiempo')
            ->first();

        return IndicadoresDelPanel::proporcion((int) ($respuestas->a_tiempo ?? 0), (int) ($respuestas->total ?? 0));
    }

    // Desde que se reporta hasta que alguien empieza a atenderlo (su primer paso a «en progreso»).
    private function medianaHastaAtender(AlcanceIncidentes $alcance, CarbonImmutable $desde): ?int
    {
        $primeraAtencion = HistorialIncidente::query()->toBase()
            ->selectRaw('incidente_id, min(created_at) as momento')
            ->where('campo', CampoHistorial::Estado->value)
            ->where('valor_nuevo', Estado::EnProgreso->value)
            ->where('valor_anterior', Estado::Abierto->value)
            ->groupBy('incidente_id');

        $segundos = array_values($this->visibles($alcance)->toBase()
            ->joinSub($primeraAtencion, 'atencion', 'atencion.incidente_id', '=', 'incidentes.id')
            ->where('incidentes.created_at', '>=', $desde)
            ->selectRaw('timestampdiff(second, incidentes.created_at, atencion.momento) as segundos')
            ->pluck('segundos')
            ->map(fn (mixed $valor) => (int) $valor)
            ->all());

        return IndicadoresDelPanel::mediana($segundos);
    }

    // De las soluciones que el solicitante respondió en el periodo, qué parte devolvió por no estar resuelto.
    private function devueltos(AlcanceIncidentes $alcance, CarbonImmutable $desde): ?float
    {
        $respuestas = HistorialIncidente::query()->toBase()
            ->where('campo', CampoHistorial::Estado->value)
            ->where('valor_anterior', Estado::Resuelto->value)
            ->where('created_at', '>=', $desde)
            ->whereIn('incidente_id', $this->visibles($alcance)->select('id'))
            ->selectRaw('count(*) as total, count(case when valor_nuevo = ? then 1 end) as devueltos', [Estado::EnProgreso->value])
            ->first();

        return IndicadoresDelPanel::proporcion((int) ($respuestas->devueltos ?? 0), (int) ($respuestas->total ?? 0));
    }
}
