<?php

namespace App\Dominio;

// Acciones sensibles que quedan registradas en la auditoría. Los cambios de los incidentes
// no están aquí: tienen su propio historial.
enum AccionAuditoria: string
{
    case LoginFallido = 'login_fallido';
    case ContrasenaCambiada = 'contrasena_cambiada';
    case ContrasenaRestablecida = 'contrasena_restablecida';
    case UsuarioCreado = 'usuario_creado';
    case UsuarioModificado = 'usuario_modificado';
    case UsuarioDesactivado = 'usuario_desactivado';
    case UsuarioReactivado = 'usuario_reactivado';
    case RolAsignado = 'rol_asignado';
    case RolCreado = 'rol_creado';
    case RolRenombrado = 'rol_renombrado';
    case RolEliminado = 'rol_eliminado';
    case PermisoConcedido = 'permiso_concedido';
    case PermisoRetirado = 'permiso_retirado';
}
