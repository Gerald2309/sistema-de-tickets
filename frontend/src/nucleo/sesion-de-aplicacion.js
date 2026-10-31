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
      alCerrarSesion.forEach((limpieza) => limpieza())
      const actual = router.currentRoute.value
      if (actual.name === 'ingresar') {
        queryClient.clear()
        return
      }
      const expirada = sesion.motivoDeSalida === 'expirada'
      const destino = {
        name: 'ingresar',
        query: expirada && actual.fullPath !== '/' ? { destino: actual.fullPath } : {},
      }
      // La caché se borra cuando la pantalla ya se desmontó: antes, sus consultas se volverían a pedir sin token.
      // Si la navegación no llega (por ejemplo, la pantalla de ingreso es de una versión anterior), se carga la
      // página de ingreso desde cero: nunca quedan a la vista datos de una sesión cerrada.
      const salir = async () => {
        try {
          const fallo = await router.replace(destino)
          if (fallo) {
            throw fallo
          }
          queryClient.clear()
        } catch {
          window.location.assign(router.resolve(destino).href)
        }
      }
      // Cerrar sesión sale con la cortina; una sesión vencida vuelve directo, con su mensaje.
      expirada ? salir() : cubrirCon('Hasta pronto', salir)
    },
  )
}
