<?php

namespace App\Dominio;

// Catálogo fijo de permisos: única fuente de verdad. La tabla `permisos` se siembra desde aquí.
enum ClavePermiso: string
{
    case CrearIncidente = 'crear_incidente';
    case VerPropios = 'ver_propios';
    case EditarPropios = 'editar_propios';
    case VerTodos = 'ver_todos';
    case EditarTodos = 'editar_todos';
    case CambiarEstado = 'cambiar_estado';
    case Reasignar = 'reasignar';
    case Borrar = 'borrar';
    case VerHistorial = 'ver_historial';
    case VerDashboard = 'ver_dashboard';
    case AdministrarRoles = 'administrar_roles';
    case AdministrarUsuarios = 'administrar_usuarios';
}
