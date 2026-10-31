<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CambiosVigilados;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\TransicionInvalida;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarIncidente
{
    public function __construct(private readonly RegistrarHistorial $registrarHistorial) {}

    /**
     * @param  array<string, mixed>  $datos  ya validados y con el permiso de cada campo comprobado
     *
     * @throws TransicionInvalida si el estado nuevo no es un paso permitido del ciclo de vida.
     * @throws ValidationException si se resuelve sin indicar la solución.
     */
    public function ejecutar(User $autor, Incidente $incidente, array $datos): Incidente
    {
        return DB::transaction(function () use ($autor, $incidente, $datos) {
            // Se relee con bloqueo: dos cambios de estado simultáneos no pueden validarse contra el mismo estado.
            $actual = Incidente::lockForUpdate()->findOrFail($incidente->id);
            $antes = $this->valoresVigilados($actual);

            $this->validarCambioDeEstado($actual, $datos);

            $actual->fill($datos);

            if ($actual->isDirty()) {
                $actual->save();

                $this->registrarHistorial->ejecutar(
                    $actual,
                    $autor,
                    CambiosVigilados::modificaciones($antes, $this->valoresVigilados($actual)),
                );
            }

            return $actual->load(Incidente::RELACIONES_VISIBLES);
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function validarCambioDeEstado(Incidente $actual, array $datos): void
    {
        if (! isset($datos['estado'])) {
            return;
        }

        $nuevo = Estado::from($datos['estado']);

        // Pedir el estado en el que ya está no es una transición: no cambia nada.
        if ($nuevo === $actual->estado) {
            return;
        }

        $actual->estado->pasarA($nuevo);

        // Una solución anterior (de una resolución previa) sirve si no llega una nueva.
        if ($nuevo->exigeSolucion() && blank($datos['solucion'] ?? $actual->solucion)) {
            throw ValidationException::withMessages(['solucion' => [trans('incidentes.solucion_obligatoria')]]);
        }
    }

    /**
     * Valores como texto, con las claves de CampoHistorial, tal como se guardan en el historial.
     *
     * @return array<string, string|null>
     */
    private function valoresVigilados(Incidente $incidente): array
    {
        return [
            CampoHistorial::Titulo->value => $incidente->titulo,
            CampoHistorial::Estado->value => $incidente->estado->value,
            CampoHistorial::Prioridad->value => $incidente->prioridad->value,
            CampoHistorial::Categoria->value => $incidente->categoria->value,
            CampoHistorial::UsuarioAsignado->value => $incidente->usuario_asignado_id === null
                ? null
                : (string) $incidente->usuario_asignado_id,
            CampoHistorial::FechaVencimiento->value => $incidente->fecha_vencimiento->copy()->utc()->format('Y-m-d H:i:s'),
        ];
    }
}
