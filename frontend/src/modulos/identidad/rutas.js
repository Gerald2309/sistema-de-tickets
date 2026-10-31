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
]

/** @type {import('vue-router').RouteRecordRaw[]} dentro de la estructura de la aplicación */
export const rutas = [
  {
    path: 'mi-cuenta',
    name: 'mi-cuenta',
    component: () => import('./vistas/VistaMiCuenta.vue'),
    meta: { permiso: 'autenticado', titulo: 'Mi cuenta' },
  },
]
