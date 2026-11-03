<?php

namespace App\Automatizaciones\Acciones;

use App\Automatizaciones\Dominio\Disparador;
use App\Automatizaciones\Dominio\Hechos;
use App\Automatizaciones\Models\Automatizacion;
use App\Automatizaciones\Models\EjecucionDeAutomatizacion;
use App\Incidentes\Models\Incidente;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Aplica a un incidente las automatizaciones activas de un disparador, en orden. Cada una se aplica una
// sola vez por incidente y en su propia transacción. Si una acción no se puede hacer (cerrar uno que no
// está resuelto, asignar a quien ya no atiende), esa automatización se detiene ahí y anota por qué; lo
// que ya hizo se conserva. Un error inesperado deshace la automatización entera y la cola la reintenta.
class AplicarAutomatizaciones
{
    public function __construct(private readonly EjecutarAccion $ejecutarAccion) {}

    public function ejecutar(int $incidenteId, Disparador $disparador): void
    {
        $automatizaciones = Automatizacion::query()->activasPara($disparador)->with('autor')->get();

        foreach ($automatizaciones as $automatizacion) {
            $this->aplicarA($automatizacion, $incidenteId);
        }
    }

    public function aplicarA(Automatizacion $automatizacion, int $incidenteId): void
    {
        DB::transaction(fn () => $this->aplicar($automatizacion, $incidenteId));
    }

    private function aplicar(Automatizacion $automatizacion, int $incidenteId): void
    {
        $ejecucion = ['automatizacion_id' => $automatizacion->id, 'incidente_id' => $incidenteId];
        // Se lee aquí, y no antes, para ver lo que dejaron las automatizaciones anteriores.
        $incidente = Incidente::find($incidenteId);

        // La clave única de la ejecución decide quién la aplica: un reintento o un segundo disparo no hace nada.
        if ($incidente === null || EjecucionDeAutomatizacion::query()->insertOrIgnore($ejecucion) === 0) {
            return;
        }

        EjecucionDeAutomatizacion::query()->where($ejecucion)->update($this->hacerAcciones($automatizacion, $incidente));
    }

    /**
     * @return array{acciones_hechas: int, error: string|null}
     */
    private function hacerAcciones(Automatizacion $automatizacion, Incidente $incidente): array
    {
        if (! $automatizacion->autor->activo) {
            return ['acciones_hechas' => 0, 'error' => 'Quien la guardó ya no está activo, y lo que hace queda a su nombre.'];
        }

        $hechas = 0;

        try {
            foreach ($automatizacion->flujo()->accionesPara($this->hechosDe($incidente)) as $accion) {
                $this->ejecutarAccion->ejecutar($accion, $incidente->refresh(), $automatizacion);
                $hechas++;
            }
        } catch (DomainException|ValidationException $error) {
            return ['acciones_hechas' => $hechas, 'error' => Str::limit($error->getMessage(), 250)];
        }

        return ['acciones_hechas' => $hechas, 'error' => null];
    }

    private function hechosDe(Incidente $incidente): Hechos
    {
        return new Hechos($incidente->cliente_id, $incidente->categoria, $incidente->prioridad, "{$incidente->titulo}\n{$incidente->descripcion}");
    }
}
