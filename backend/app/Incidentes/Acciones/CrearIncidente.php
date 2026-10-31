<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class CrearIncidente
{
    public function __construct(
        private readonly RegistrarHistorial $registrarHistorial,
        private readonly AvisarCambios $avisarCambios,
    ) {}

    /**
     * El creador, su empresa y el estado inicial no vienen de la petición: son el usuario autenticado, la
     * empresa a la que pertenece en este momento (F-10) y "abierto".
     *
     * @param  array<string, mixed>  $datos  ya validados
     */
    public function ejecutar(User $creador, array $datos): Incidente
    {
        return DB::transaction(function () use ($creador, $datos) {
            $incidente = Incidente::create($datos + [
                'usuario_creador_id' => $creador->id,
                'cliente_id' => $creador->cliente_id,
                'estado' => Estado::Abierto,
            ]);

            $this->registrarHistorial->ejecutar($incidente, $creador, [EntradaHistorial::creacion()]);
            $this->avisarCambios->registrar($incidente, TipoDeCambio::Creado);

            // Se recarga para traer los valores por defecto de la base (prioridad y categoría), que viven solo allí.
            return $incidente->refresh()->load(Incidente::RELACIONES_VISIBLES);
        });
    }
}
