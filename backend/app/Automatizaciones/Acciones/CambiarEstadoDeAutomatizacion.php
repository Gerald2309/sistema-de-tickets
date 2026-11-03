<?php

namespace App\Automatizaciones\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Automatizaciones\Models\Automatizacion;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

// Enciende o apaga una automatización. Quien la enciende pasa a ser su autor: lo que haga queda a su nombre.
class CambiarEstadoDeAutomatizacion
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    public function ejecutar(User $usuario, Automatizacion $automatizacion, bool $activa, ?string $ip): Automatizacion
    {
        if ($automatizacion->activa === $activa) {
            return $automatizacion;
        }

        return DB::transaction(function () use ($usuario, $automatizacion, $activa, $ip) {
            $automatizacion->activa = $activa;

            if ($activa) {
                $automatizacion->autor_id = $usuario->id;
            }

            $automatizacion->save();
            $this->registrarAuditoria->ejecutar(
                $activa ? AccionAuditoria::AutomatizacionActivada : AccionAuditoria::AutomatizacionDesactivada,
                $usuario->id,
                null,
                "«{$automatizacion->nombre}»",
                $ip,
            );

            return $automatizacion;
        });
    }
}
