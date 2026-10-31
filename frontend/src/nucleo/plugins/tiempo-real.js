import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { watch } from 'vue'
import { http } from '@/compartido/api/cliente.js'
import { tiempoReal } from '@/compartido/tiempo-real.js'
import { crearAgrupador } from '@/compartido/utilidades/agrupador.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { invalidarIncidentes } from '@/modulos/incidentes/index.js'

/**
 * Los avisos que llegan casi juntos (varias personas a la vez) se atienden en una sola tanda. Un lote ya llega
 * en un solo aviso; la espera es corta para cumplir los 2 s de RF-F45.
 */
const AGRUPAR_MS = 300

/**
 * Tiempo real con Reverb (F-12, RF-F45). El aviso solo dice qué incidentes cambiaron; esos datos se vuelven
 * a pedir a la API con el token de la persona, que decide qué puede ver. Si el WebSocket no está disponible,
 * nada se rompe: las consultas vuelven al refresco periódico (compartido/tiempo-real.js).
 * @param {{ queryClient: import('@tanstack/vue-query').QueryClient, alTerminarLaSesion: (limpieza: () => void) => void }} piezas
 * @returns {Promise<void>}
 */
export async function conectarTiempoReal({ queryClient, alTerminarLaSesion }) {
  const leida = await leerClave()
  if (!leida) {
    return
  }
  const clave = leida
  const sesion = useSesion()
  /** @type {Echo<'reverb'> | null} */
  let echo = null
  let estuvoConectado = false
  const avisos = crearAgrupador(AGRUPAR_MS, (ids) => invalidarIncidentes(queryClient, ids))

  /** @param {{ ids?: unknown }} aviso lo que llega del WebSocket se valida antes de usarlo */
  const alAvisar = (aviso) => avisos.agregar(Array.isArray(aviso?.ids) ? aviso.ids : [])

  function desconectar() {
    avisos.cancelar()
    echo?.disconnect()
    echo = null
    tiempoReal.conectado = false
    estuvoConectado = false
  }

  /**
   * Un canal según lo que la persona ve (routes/channels.php del backend): todos los incidentes o solo los
   * suyos. Si cambian sus permisos, se vuelve a conectar.
   * @param {{ id: number, veTodo: boolean }} persona
   */
  function conectar({ id, veTodo }) {
    desconectar()
    echo = new Echo({
      broadcaster: 'reverb',
      Pusher,
      key: clave,
      wsHost: window.location.hostname,
      wsPort: Number(window.location.port) || 80,
      wssPort: Number(window.location.port) || 443,
      forceTLS: window.location.protocol === 'https:',
      enabledTransports: ['ws', 'wss'],
      // La autorización del canal pasa por el cliente HTTP de siempre: mismo origen y token Bearer.
      channelAuthorization: {
        endpoint: '/api/v1/broadcasting/auth',
        transport: 'ajax',
        customHandler: ({ socketId, channelName }, responder) => {
          http
            .post('broadcasting/auth', { socket_id: socketId, channel_name: channelName })
            .then(({ data }) => responder(null, data))
            .catch((error) => responder(error, null))
        },
      },
    })
    echo.connector.pusher.connection.bind('state_change', (/** @type {{ current: string }} */ { current }) => {
      const conectado = current === 'connected'
      // Al reconectar pudieron perderse avisos: se refresca todo lo de incidentes una vez.
      if (conectado && estuvoConectado && !tiempoReal.conectado) {
        invalidarIncidentes(queryClient)
      }
      estuvoConectado ||= conectado
      tiempoReal.conectado = conectado
    })
    echo.private(veTodo ? 'incidentes' : `usuario.${id}`).listen('.incidentes.cambiados', alAvisar)
  }

  watch(
    () => (sesion.perfil ? { id: sesion.perfil.id, veTodo: sesion.tiene('ver_todos') } : null),
    (persona, anterior) => {
      if (!persona) {
        desconectar()
      } else if (persona.id !== anterior?.id || persona.veTodo !== anterior?.veTodo) {
        conectar(persona)
      }
    },
    { immediate: true },
  )
  alTerminarLaSesion(desconectar)
}

/**
 * La clave pública de Reverb llega en tiempo de ejecución desde el contenedor (no se compila en el paquete).
 * @returns {Promise<string | null>}
 */
async function leerClave() {
  try {
    const respuesta = await fetch('/configuracion.json', { cache: 'no-store' })
    const configuracion = respuesta.ok ? await respuesta.json() : null
    const clave = configuracion?.tiempoReal?.clave
    return typeof clave === 'string' && clave !== '' ? clave : null
  } catch {
    return null
  }
}
