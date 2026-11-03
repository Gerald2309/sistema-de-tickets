<?php

namespace App\Automatizaciones\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

// Elimina una automatización y su registro de ejecuciones. Lo que ya hizo sigue en el historial de cada
// incidente, con su nombre como origen.
class EliminarAutomatizacion
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    public function ejecutar(User $usuario, Automatizacion $automatizacion, ?string $ip): void
    {
        DB::transaction(function () use ($usuario, $automatizacion, $ip) {
            $automatizacion->delete();
            $this->registrarAuditoria->ejecutar(AccionAuditoria::AutomatizacionEliminada, $usuario->id, null, "«{$automatizacion->nombre}»", $ip);
        });
    }
}
