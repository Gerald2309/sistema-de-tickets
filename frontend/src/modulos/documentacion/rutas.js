/**
 * Rutas del módulo de documentación. Es abierta: la ve cualquiera, con sesión o sin ella (nucleo/enrutador/acceso.js).
 */

/** @type {import('vue-router').RouteRecordRaw[]} fuera de la estructura de la aplicación: tiene su propia barra y menú */
export const rutas = [
  {
    path: '/documentacion/:pagina?',
    name: 'documentacion',
    component: () => import('./vistas/VistaDocumentacion.vue'),
    props: true,
    meta: { permiso: 'abierta', titulo: 'Documentación' },
  },
]
