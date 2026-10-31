<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class ModificarCliente
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * Renombra o activa y desactiva una empresa. Desactivarla no cambia a sus personas ni sus incidentes:
     * solo impide asignarla a alguien más. Cada cambio queda como un registro de auditoría propio.
     *
     * @param  array{nombre?: string, activo?: bool}  $cambios
     */
    public function ejecutar(User $administrador, Cliente $cliente, array $cambios, ?string $ip): Cliente
    {
        return DB::transaction(function () use ($administrador, $cliente, $cambios, $ip) {
            $cliente = Cliente::findOrFail($cliente->id);
            $nombreAnterior = $cliente->nombre;
            $cliente->fill($cambios);
            $cambiados = array_keys($cliente->getDirty());
            $cliente->save();

            $registrar = fn (string $detalle) => $this->registrarAuditoria->ejecutar(AccionAuditoria::ClienteModificado, $administrador->id, null, $detalle, $ip);

            if (in_array('nombre', $cambiados, true)) {
                $registrar("Empresa «{$nombreAnterior}» → «{$cliente->nombre}»");
            }
            if (in_array('activo', $cambiados, true)) {
                $registrar(($cliente->activo ? 'Reactivada' : 'Desactivada')." la empresa «{$cliente->nombre}»");
            }

            return $cliente->loadCount('usuarios');
        });
    }
}
