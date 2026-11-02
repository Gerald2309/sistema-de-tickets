/**
 * Rutas del módulo de identidad. Cada una declara su permiso (nucleo/enrutador/acceso.js).
 */

/** @type {import('vue-router').RouteRecordRaw[]} fuera de la estructura con barra y menú */
export const rutasPublicas = [
  {
    path: '/ingresar',
    name: 'ingresar',
    component: () => import('./vistas/VistaIngreso.vue'),
    meta: { permiso: 'publica', titulo: 'Iniciar sesión' },
  },
  {
    path: '/cambiar-contrasena',
    name: 'cambiar-contrasena',
    component: () => import('./vistas/VistaCambioDeContrasena.vue'),
    meta: { permiso: 'cambio-de-contrasena', titulo: 'Cambiar la contraseña' },
  },
]

/** @type {import('vue-router').RouteRecordRaw[]} dentro de la estructura de la aplicación */
export const rutas = [
  {
    path: 'mi-cuenta',
    name: 'mi-cuenta',
    component: () => import('./vistas/VistaMiCuenta.vue'),
    meta: { permiso: 'autenticado', titulo: 'Mi cuenta' },
  },
  {
    path: 'administracion/usuarios',
    name: 'usuarios',
    component: () => import('./vistas/VistaUsuarios.vue'),
    meta: { permiso: 'administrar_usuarios', titulo: 'Usuarios' },
  },
  {
    path: 'administracion/roles',
    name: 'roles',
    component: () => import('./vistas/VistaRoles.vue'),
    meta: { permiso: 'administrar_roles', titulo: 'Roles y permisos' },
  },
  {
    path: 'administracion/empresas',
    name: 'empresas',
    component: () => import('./vistas/VistaEmpresas.vue'),
    meta: { permiso: 'administrar_usuarios', titulo: 'Empresas' },
  },
]
