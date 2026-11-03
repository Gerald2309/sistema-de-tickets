<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Consultas\PlazosDeLaEmpresa;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\Prioridad;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Eventos\IncidenteCreado;
use App\Incidentes\Models\Incidente;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class CrearIncidente
{
    public function __construct(
        private readonly RegistrarHistorial $registrarHistorial,
        private readonly AvisarCambios $avisarCambios,
        private readonly PlazosDeLaEmpresa $plazosDeLaEmpresa,
    ) {}

    /**
     * El creador, su empresa y el estado inicial no vienen de la petición: son el usuario autenticado, la
     * empresa a la que pertenece en este momento (F-10) y "abierto". Los límites salen del acuerdo de servicio de
     * esa empresa; una fecha de vencimiento escrita a mano reemplaza a la calculada.
     *
     * Con clave de idempotencia, repetir la petición devuelve el incidente ya creado en vez de otro igual
     * (`wasRecentlyCreated` dice cuál de los dos casos fue).
     *
     * @param  array<string, mixed>  $datos  ya validados
     */
    public function ejecutar(User $creador, array $datos, ?string $claveDeIdempotencia = null): Incidente
    {
        $previo = $this->yaCreado($creador, $claveDeIdempotencia);
        if ($previo !== null) {
            return $previo;
        }

        try {
            return DB::transaction(function () use ($creador, $datos, $claveDeIdempotencia) {
                $limites = $this->plazosDeLaEmpresa->limites(
                    $creador->cliente_id,
                    Prioridad::tryFrom($datos['prioridad'] ?? '') ?? Prioridad::PREDETERMINADA,
                    Date::now()->toImmutable(),
                );

                $incidente = Incidente::create($datos + [
                    'fecha_vencimiento' => $limites['resolucion'],
                    'vence_respuesta_en' => $limites['respuesta'],
                    'usuario_creador_id' => $creador->id,
                    'cliente_id' => $creador->cliente_id,
                    'estado' => Estado::Abierto,
                    'clave_de_idempotencia' => $claveDeIdempotencia,
                ]);

                $this->registrarHistorial->ejecutar($incidente, $creador, [EntradaHistorial::creacion()]);
                $this->avisarCambios->registrar($incidente, TipoDeCambio::Creado);
                event(new IncidenteCreado($incidente->id));

                // Se recarga para traer los valores por defecto de la base (prioridad y categoría), que viven solo allí.
                return $incidente->refresh()->load(Incidente::RELACIONES_VISIBLES);
            });
        } catch (UniqueConstraintViolationException $duplicado) {
            // Dos envíos simultáneos con la misma clave: el otro ya lo creó y la transacción de este se deshizo.
            return $this->yaCreado($creador, $claveDeIdempotencia) ?? throw $duplicado;
        }
    }

    private function yaCreado(User $creador, ?string $claveDeIdempotencia): ?Incidente
    {
        return $claveDeIdempotencia === null ? null : Incidente::query()
            ->where('usuario_creador_id', $creador->id)
            ->where('clave_de_idempotencia', $claveDeIdempotencia)
            ->first();
    }
}
