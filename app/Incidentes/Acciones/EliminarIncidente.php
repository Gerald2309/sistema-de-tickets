<?php

namespace App\Incidentes\Acciones;

use App\Identidad\Models\User;
use App\Incidentes\Dominio\EntradaHistorial;
use App\Incidentes\Dominio\TipoDeCambio;
use App\Incidentes\Models\Incidente;
use Illuminate\Support\Facades\DB;

class EliminarIncidente
{
    public function __construct(
        private readonly RegistrarHistorial $registrarHistorial,
        private readonly AvisarCambios $avisarCambios,
    ) {}

    // Borrado lógico: el incidente y su historial se conservan (RF-08).
    public function ejecutar(User $autor, Incidente $incidente): void
    {
        DB::transaction(function () use ($autor, $incidente) {
            $incidente->delete();

            $this->registrarHistorial->ejecutar($incidente, $autor, [EntradaHistorial::borrado()]);
            $this->avisarCambios->registrar($incidente, TipoDeCambio::Eliminado);
        });
    }
}
