<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Dominio\Vencimiento;
use App\Incidentes\Models\Incidente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TraspasarIncidentesAbiertos
{
    // Filas por tramo: un traspaso de miles no arma una sola consulta gigante ni bloquea de una vez.
    public const POR_TRAMO = 200;

    public function __construct(
        private readonly RegistrarHistorial $registrarHistorial,
        private readonly AvisarCambios $avisarCambios,
    ) {}

    /**
     * Los incidentes sin resolver asignados a una persona (por ejemplo, antes de desactivarla) pasan a otra o
     * vuelven a la cola sin asignar. Lo resuelto y lo cerrado conservan a su persona: es historia. Todo o nada,
     * por tramos, con una entrada de historial por incidente y un solo aviso en tiempo real.
     *
     * @return int cuántos se traspasaron
     */
    public function ejecutar(User $autor, User $de, ?User $a): int
    {
        return DB::transaction(function () use ($autor, $de, $a) {
            $traspasados = 0;
            $this->abiertosDe($de)
                ->select(['id', 'usuario_creador_id', 'usuario_asignado_id'])
                ->lockForUpdate()
                ->chunkById(self::POR_TRAMO, function (Collection $tramo) use ($autor, $de, $a, &$traspasados) {
                    $this->traspasar($tramo, $autor, $de, $a);
                    $traspasados += $tramo->count();
                });

            return $traspasados;
        });
    }

    public function contar(User $de): int
    {
        return $this->abiertosDe($de)->count();
    }

    /**
     * @return Builder<Incidente>
     */
    private function abiertosDe(User $usuario): Builder
    {
        return Incidente::query()
            ->withoutEagerLoads()
            ->where('usuario_asignado_id', $usuario->id)
            ->whereIn('estado', array_map(fn (Estado $estado) => $estado->value, Vencimiento::estadosQueVencen()));
    }

    /**
     * Un UPDATE y un INSERT del historial por tramo, en vez de dos consultas por incidente.
     *
     * @param  Collection<int, Incidente>  $tramo
     */
    private function traspasar(Collection $tramo, User $autor, User $de, ?User $a): void
    {
        Incidente::whereKey($tramo->modelKeys())->update(['usuario_asignado_id' => $a?->id, 'updated_at' => now()]);

        $this->registrarHistorial->enBloque(
            array_values($tramo->modelKeys()),
            $autor,
            EntradaHistorial::modificacion(CampoHistorial::UsuarioAsignado, (string) $de->id, $a ? (string) $a->id : null),
        );

        foreach ($tramo as $incidente) {
            $incidente->usuario_asignado_id = $a?->id;
            // Quien los tenía también se entera: salen de su lista.
            $this->avisarCambios->registrar($incidente, TipoDeCambio::Actualizado, $de->id);
        }
    }
}
