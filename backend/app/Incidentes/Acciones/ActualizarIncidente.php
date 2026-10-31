<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\CambiosVigilados;
use App\Incidentes\Dominio\CampoHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Excepciones\TransicionInvalida;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActualizarIncidente
{
    public function __construct(
        private readonly RegistrarHistorial $registrarHistorial,
        private readonly AvisarCambios $avisarCambios,
    ) {}

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
            $asignadoAnterior = $actual->usuario_asignado_id;

            $this->validarCambioDeEstado($actual, $datos);

            $actual->fill($datos);

            if ($actual->isDirty()) {
                $actual->save();

                $this->registrarHistorial->ejecutar(
                    $actual,
                    $autor,
                    CambiosVigilados::modificaciones($antes, $this->valoresVigilados($actual)),
                );
                // Quien deja de estar asignado también se entera: el incidente sale de su lista.
                $this->avisarCambios->registrar($actual, TipoDeCambio::Actualizado, $asignadoAnterior);
            }

            return $actual->load(Incidente::RELACIONES_VISIBLES);
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function validarCambioDeEstado(Incidente $actual, array $datos): void
    {
        $nuevo = isset($datos['estado']) ? Estado::from($datos['estado']) : null;

        // Pedir el estado en el que ya está no es una transición: no cambia nada.
        $cambiaDeEstado = $nuevo !== null && $nuevo !== $actual->estado;

        if ($cambiaDeEstado) {
            $actual->estado->pasarA($nuevo);
        }

        $resuelve = $cambiaDeEstado && $nuevo->exigeSolucion();

        // La solución se escribe una sola vez, en la petición que resuelve el incidente.
        if (isset($datos['solucion']) && ! $resuelve) {
            throw ValidationException::withMessages(['solucion' => [trans('incidentes.solucion_solo_al_resolver')]]);
        }

        // Una solución anterior (de una resolución previa) sirve si no llega una nueva.
        if ($resuelve && blank($datos['solucion'] ?? $actual->solucion)) {
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
            CampoHistorial::Descripcion->value => $incidente->descripcion,
            CampoHistorial::Estado->value => $incidente->estado->value,
            CampoHistorial::Solucion->value => $incidente->solucion,
            CampoHistorial::Prioridad->value => $incidente->prioridad->value,
            CampoHistorial::Categoria->value => $incidente->categoria->value,
            CampoHistorial::UsuarioAsignado->value => $incidente->usuario_asignado_id === null
                ? null
                : (string) $incidente->usuario_asignado_id,
            CampoHistorial::FechaVencimiento->value => $incidente->fecha_vencimiento->copy()->utc()->format('Y-m-d H:i:s'),
        ];
    }
}
