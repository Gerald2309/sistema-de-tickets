import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { idDeCodigo } from './filtros.js'
import { usePestanas } from './tienda/pestanas.js'

/**
 * Rutas del módulo de incidentes. Cada una declara su permiso (nucleo/enrutador/acceso.js); `meta.pestana`
 * marca las que viven dentro de una pestaña de ticket.
 */

const VER = ['ver_propios', 'ver_todos']

/** @type {import('vue-router').RouteRecordRaw[]} dentro de la estructura de la aplicación */
export const rutas = [
  {
    path: 'panel',
    name: 'panel',
    component: () => import('./vistas/VistaPanel.vue'),
    meta: { permiso: 'ver_dashboard', titulo: 'Panel' },
  },
  {
    path: 'incidentes',
    name: 'incidentes',
    component: () => import('./vistas/VistaListado.vue'),
    meta: { permiso: VER, titulo: 'Incidentes' },
  },
  {
    path: 'incidentes/nuevo',
    name: 'nuevo-incidente',
    component: () => import('./vistas/VistaFormulario.vue'),
    meta: { permiso: 'crear_incidente', titulo: 'Nuevo incidente' },
  },
  {
    path: 'incidentes/:id(\\d+)',
    name: 'incidente',
    component: () => import('./vistas/VistaDetalle.vue'),
    props: (ruta) => ({ id: Number(ruta.params.id) }),
    meta: { permiso: VER, titulo: 'Incidente', pestana: 'detalle' },
  },
  {
    path: 'incidentes/:id(\\d+)/editar',
    name: 'editar-incidente',
    component: () => import('./vistas/VistaFormulario.vue'),
    props: (ruta) => ({ id: Number(ruta.params.id) }),
    meta: {
      permiso: ['editar_propios', 'editar_todos', 'cambiar_estado', 'reasignar'],
      titulo: 'Editar incidente',
      pestana: 'edicion',
    },
  },
  // Un código escrito a mano (INC-000605) lleva a su incidente.
  {
    path: 'incidentes/:codigo(INC-\\d+)',
    redirect: (ruta) => {
      const id = idDeCodigo(String(ruta.params.codigo))
      return id ? { name: 'incidente', params: { id } } : { name: 'incidentes' }
    },
  },
]

/**
 * Guarda global que abre o activa la pestaña del ticket al entrar en su pantalla (RF-F11). Va después de la
 * de permisos: solo abre pestañas de lo que la persona puede ver.
 * @param {import('vue-router').RouteLocationNormalized} destino
 * @returns {boolean}
 */
export function guardaDePestanas(destino) {
  const pestanas = usePestanas()
  const pantalla = /** @type {'detalle' | 'edicion' | undefined} */ (destino.meta.pestana)
  if (!pantalla) {
    pestanas.desactivar()
    return true
  }
  if (pestanas.abrir(Number(destino.params.id), pantalla)) {
    return true
  }
  useNotificaciones().info(
    'No se abrió el incidente',
    'Las 8 pestañas tienen cambios sin guardar: guarde o cierre alguna.',
  )
  return false
}
