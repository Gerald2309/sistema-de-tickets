/**
 * Rutas del módulo de auditoría. Cada una declara su permiso (nucleo/enrutador/acceso.js).
 */

/** @type {import('vue-router').RouteRecordRaw[]} dentro de la estructura de la aplicación */
export const rutas = [
  {
    path: 'administracion/auditoria',
    name: 'auditoria',
    component: () => import('./vistas/VistaAuditoria.vue'),
    meta: { permiso: 'ver_auditoria', titulo: 'Auditoría' },
  },
]
