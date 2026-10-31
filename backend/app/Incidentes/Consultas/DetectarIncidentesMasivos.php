<?php

namespace App\Incidentes\Consultas;

use App\Incidentes\Dominio\AvisoDeIncidenteMasivo;
use App\Incidentes\Dominio\Categoria;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\PalabrasRepetidas;
use App\Incidentes\Models\Incidente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DetectarIncidentesMasivos
{
    /**
     * Categorías con muchos incidentes sin resolver, de varias personas, en la ventana de tiempo, de
     * la que más a la que menos incidentes. Una consulta agrupada decide; solo para las categorías
     * que superan el umbral se leen los textos, y siempre acotados.
     *
     * @return list<AvisoDeIncidenteMasivo>
     */
    public function ejecutar(Carbon $ahora): array
    {
        $desde = $ahora->copy()->subHours((int) config('incidentes.avisos.ventana_horas'));

        $avisos = $this->sinResolverDesde($desde)
            ->selectRaw('categoria, count(*) as incidentes, count(distinct usuario_creador_id) as personas')
            ->groupBy('categoria')
            ->havingRaw('count(*) >= ? and count(distinct usuario_creador_id) >= ?', [
                (int) config('incidentes.avisos.minimo_incidentes'),
                (int) config('incidentes.avisos.minimo_personas'),
            ])
            ->toBase()
            ->get()
            ->map(function (object $grupo) use ($desde) {
                $categoria = Categoria::from($grupo->categoria);

                return new AvisoDeIncidenteMasivo(
                    $categoria,
                    (int) $grupo->incidentes,
                    (int) $grupo->personas,
                    $this->palabrasQueSeRepiten($categoria, $desde),
                    $desde,
                );
            })
            ->all();

        usort($avisos, fn (AvisoDeIncidenteMasivo $a, AvisoDeIncidenteMasivo $b) => $b->incidentes <=> $a->incidentes ?: strcmp($a->categoria->value, $b->categoria->value));

        return $avisos;
    }

    /**
     * @return list<string>
     */
    private function palabrasQueSeRepiten(Categoria $categoria, Carbon $desde): array
    {
        $textos = $this->sinResolverDesde($desde)
            ->where('categoria', $categoria->value)
            ->latest('id')
            ->limit((int) config('incidentes.avisos.maximo_textos'))
            ->get(['titulo', 'descripcion'])
            ->map(fn (Incidente $incidente) => "$incidente->titulo $incidente->descripcion")
            ->all();

        return PalabrasRepetidas::desde(
            array_values($textos),
            (int) config('seguridad.busqueda.min_token'),
            (int) config('incidentes.avisos.maximo_palabras'),
        );
    }

    /**
     * @return Builder<Incidente>
     */
    private function sinResolverDesde(Carbon $desde)
    {
        return Incidente::query()
            ->without(['creador', 'asignado'])
            ->whereIn('estado', [Estado::Abierto->value, Estado::EnProgreso->value])
            ->where('created_at', '>=', $desde);
    }
}
