<?php

namespace App\Identidad\Acciones;

use App\Auditoria\Acciones\RegistrarAuditoria;
use App\Auditoria\Dominio\AccionAuditoria;
use App\Identidad\Models\User;
use Illuminate\Support\Facades\DB;

class CrearUsuario
{
    public function __construct(private readonly RegistrarAuditoria $registrarAuditoria) {}

    /**
     * @param  array{name: string, email: string, password: string, rol_id: int, cliente_id: int|null}  $datos
     */
    public function ejecutar(User $administrador, array $datos, ?string $ip): User
    {
        return DB::transaction(function () use ($administrador, $datos, $ip) {
            // La contraseña la puso el administrador: la persona la cambia al entrar por primera vez.
            $usuario = User::create($datos + ['debe_cambiar_contrasena' => true])->refresh()->load(['rol', 'cliente']);

            $empresa = $usuario->cliente ? " · Empresa «{$usuario->cliente->nombre}»" : '';
            $this->registrarAuditoria->ejecutar(AccionAuditoria::UsuarioCreado, $administrador->id, $usuario->id, "Rol «{$usuario->rol->nombre}»{$empresa}", $ip);

            return $usuario;
        });
    }
}
