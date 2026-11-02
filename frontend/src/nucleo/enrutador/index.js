import { createRouter, createWebHistory } from 'vue-router'
import { navegacion as navegacionAuditoria, rutas as rutasAuditoria } from '@/modulos/auditoria/index.js'
import {
  navegacion as navegacionIdentidad,
  rutas as rutasIdentidad,
  rutasPublicas,
  useSesion,
} from '@/modulos/identidad/index.js'
import {
  guardaDePestanas,
  navegacion as navegacionIncidentes,
  rutas as rutasIncidentes,
} from '@/modulos/incidentes/index.js'
import { decidirAcceso } from './acceso.js'
import { esDeUnaVersionAnterior, olvidarRecarga, recargarHacia } from './version-nueva.js'

const NOMBRE_PRODUCTO = 'Psicol – Mesa de ayuda'

/**
 * Entradas del menú de todos los módulos, en orden. El menú y la ruta inicial salen de aquí.
 * @type {import('@/modulos/identidad/index.js').EntradaDeNavegacion[]}
 */
export const navegacion = [...navegacionIncidentes, ...navegacionIdentidad, ...navegacionAuditoria]

/**
 * Rutas de la aplicación: las públicas y, dentro de la estructura con barra y menú, las de cada módulo.
 * @type {import('vue-router').RouteRecordRaw[]}
 */
export const rutas = [
  ...rutasPublicas,
  {
    path: '/',
    component: () => import('@/nucleo/disposicion/EstructuraDeAplicacion.vue'),
    meta: { permiso: 'autenticado' },
    children: [
      {
        path: '',
        name: 'inicio',
        component: () => import('@/nucleo/paginas/PaginaInicio.vue'),
        meta: { permiso: 'autenticado' },
      },
      ...rutasIncidentes,
      ...rutasIdentidad,
      ...rutasAuditoria,
      {
        path: 'sin-permiso',
        name: 'sin-permiso',
        component: () => import('@/nucleo/paginas/PaginaSinPermiso.vue'),
        meta: { permiso: 'autenticado', titulo: 'Sin permiso' },
      },
      {
        path: ':ruta(.*)*',
        name: 'no-encontrado',
        component: () => import('@/nucleo/paginas/PaginaNoEncontrada.vue'),
        meta: { permiso: 'autenticado', titulo: 'No encontrado' },
      },
    ],
  },
]

/**
 * @param {import('vue-router').RouterHistory} [historial] en pruebas, uno en memoria
 * @returns {import('vue-router').Router}
 */
export function crearEnrutador(historial = createWebHistory()) {
  const enrutador = createRouter({
    history: historial,
    routes: rutas,
    scrollBehavior: (_destino, _origen, posicionGuardada) => posicionGuardada ?? { top: 0 },
  })

  let sesionRestaurada = false

  enrutador.beforeEach(async (destino) => {
    const sesion = useSesion()
    // La primera navegación espera a recuperar la sesión guardada (RF-F04).
    if (!sesionRestaurada) {
      sesionRestaurada = true
      await sesion.restaurar()
    }

    // La ruta más interna decide; las padre también deben permitirlo.
    for (const registro of destino.matched) {
      const decision = decidirAcceso(/** @type {any} */ (registro.meta.permiso), sesion)
      if (decision.tipo === 'ingresar') {
        return { name: 'ingresar', query: destino.fullPath === '/' ? {} : { destino: destino.fullPath } }
      }
      if (decision.tipo === 'inicio') {
        return { name: 'inicio' }
      }
      if (decision.tipo === 'cambiar-contrasena') {
        return { name: 'cambiar-contrasena', query: destino.fullPath === '/' ? {} : { destino: destino.fullPath } }
      }
      if (decision.tipo === 'sin-permiso') {
        return { name: 'sin-permiso', replace: true }
      }
    }
    return true
  })

  // Las guardas de los módulos corren después de los permisos.
  enrutador.beforeEach(guardaDePestanas)

  enrutador.afterEach((destino, _origen, fallo) => {
    const titulo = /** @type {string | undefined} */ (destino.meta.titulo)
    document.title = titulo ? `${titulo} · ${NOMBRE_PRODUCTO}` : NOMBRE_PRODUCTO
    if (!fallo) {
      olvidarRecarga()
    }
  })

  // Una pantalla que no carga porque se publicó una versión nueva: se recarga hacia ella (version-nueva.js).
  enrutador.onError((error, destino) => {
    if (!esDeUnaVersionAnterior(error) || !recargarHacia(destino.fullPath)) {
      console.error(error)
    }
  })

  return enrutador
}
