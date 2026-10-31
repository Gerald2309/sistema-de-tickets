import { watch } from 'vue'
import { conectarSesion } from '@/compartido/api/cliente.js'
import { cubrirCon } from '@/compartido/cortina.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { usePestanas } from '@/modulos/incidentes/index.js'

/** @type {Array<() => void>} */
const alCerrarSesion = []

/**
 * Registra lo que debe olvidarse al cerrar la sesión (pestañas de tickets, conexión de tiempo real…), sin
 * que la sesión conozca a quienes dependen de ella.
 * @param {() => void} limpieza
 * @returns {void}
 */
export function alTerminarLaSesion(limpieza) {
  alCerrarSesion.push(limpieza)
}

/**
 * Une la sesión con el resto de la aplicación (raíz de composición): el cliente HTTP lee el token de la
 * sesión y, cuando la sesión termina (cierre o 401), se borra la caché y se vuelve al inicio de sesión.
 * @param {{ router: import('vue-router').Router, queryClient: import('@tanstack/vue-query').QueryClient }} piezas
 * @returns {void}
 */
export function conectarAplicacion({ router, queryClient }) {
  const sesion = useSesion()

  conectarSesion({ obtenerToken: () => sesion.token, alExpirar: () => sesion.expirar() })
  alTerminarLaSesion(() => usePestanas().olvidar())

  watch(
    () => sesion.token,
    (token, anterior) => {
      if (!anterior || token) {
        return
      }
      queryClient.clear()
      alCerrarSesion.forEach((limpieza) => limpieza())
      const actual = router.currentRoute.value
      if (actual.name !== 'ingresar') {
        const expirada = sesion.motivoDeSalida === 'expirada'
        const destino = {
          name: 'ingresar',
          query: expirada && actual.fullPath !== '/' ? { destino: actual.fullPath } : {},
        }
        // Cerrar sesión sale con la cortina; una sesión vencida vuelve directo, con su mensaje.
        expirada ? router.replace(destino) : cubrirCon('Hasta pronto', () => router.replace(destino))
      }
    },
  )
}
