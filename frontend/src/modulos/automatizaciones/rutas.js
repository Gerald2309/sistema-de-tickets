/**
 * Rutas del módulo de automatizaciones. Cada una declara su permiso (nucleo/enrutador/acceso.js).
 */

const PERMISO = 'administrar_automatizaciones'

/** @type {import('vue-router').RouteRecordRaw[]} dentro de la estructura de la aplicación */
export const rutas = [
  {
    path: 'administracion/automatizaciones',
    name: 'automatizaciones',
    component: () => import('./vistas/VistaAutomatizaciones.vue'),
    meta: { permiso: PERMISO, titulo: 'Automatizaciones' },
  },
  {
    path: 'administracion/automatizaciones/nueva',
    name: 'nueva-automatizacion',
    component: () => import('./vistas/VistaEditorDeAutomatizacion.vue'),
    meta: { permiso: PERMISO, titulo: 'Nueva automatización' },
  },
  {
    path: 'administracion/automatizaciones/:id(\\d+)',
    name: 'automatizacion',
    component: () => import('./vistas/VistaEditorDeAutomatizacion.vue'),
    props: (ruta) => ({ id: Number(ruta.params.id) }),
    meta: { permiso: PERMISO, titulo: 'Automatización' },
  },
]
