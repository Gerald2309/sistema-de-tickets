<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\Cliente;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class CrearCliente
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    public function ejecutar(User $administrador, string $nombre, ?string $ip): Cliente
    {
        return DB::transaction(function () use ($administrador, $nombre, $ip) {
            $cliente = Cliente::create(['nombre' => $nombre])->refresh()->loadCount('usuarios');

            $this->registrarAuditoria->ejecutar(AccionAuditoria::ClienteCreado, $administrador->id, null, "Empresa «{$cliente->nombre}»", $ip);

            return $cliente;
        });
    }
}
