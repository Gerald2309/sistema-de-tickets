<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\Estado;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class CrearIncidente
{
    public function __construct(private readonly RegistrarHistorial $registrarHistorial) {}

    /**
     * El creador y el estado inicial no vienen de la petición: son el usuario autenticado y "abierto".
     *
     * @param  array<string, mixed>  $datos  ya validados
     */
    public function ejecutar(User $creador, array $datos): Incidente
    {
        return DB::transaction(function () use ($creador, $datos) {
            $incidente = Incidente::create($datos + [
                'usuario_creador_id' => $creador->id,
                'estado' => Estado::Abierto,
            ]);

            $this->registrarHistorial->ejecutar($incidente, $creador, [EntradaHistorial::creacion()]);

            // Se recarga para traer los valores por defecto de la base (prioridad y categoría), que viven solo allí.
            return $incidente->refresh()->load(Incidente::RELACIONES_VISIBLES);
        });
    }
}
