<?php

namespace App\Auditoria\Acciones;

use App\Auditoria\Dominio\AccionAuditoria;
use App\Auditoria\Models\Auditoria;

class RegistrarAuditoria
{
    public function ejecutar(
        AccionAuditoria $accion,
        ?int $usuarioId,
        ?int $usuarioAfectadoId = null,
        ?string $detalle = null,
        ?string $ip = null,
    ): void {
        Auditoria::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'usuario_afectado_id' => $usuarioAfectadoId,
            'detalle' => $detalle,
            'ip' => $ip,
        ]);
    }
}
