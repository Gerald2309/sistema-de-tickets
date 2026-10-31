import { storeToRefs } from 'pinia'
import { onBeforeUnmount, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { aErrorDeApi } from '@/compartido/api/errores.js'
import { cubrirCon } from '@/compartido/cortina.js'
import { destinoSeguro } from '@/compartido/utilidades/destino.js'
import { useSesion } from '../tienda/sesion.js'

/**
 * Caso de uso del inicio de sesión (RF-F01): credenciales, error genérico, espera tras el límite de
 * intentos y regreso a la ruta que se pidió antes de iniciar sesión.
 */
export function useIngreso() {
  const sesion = useSesion()
  const ruta = useRoute()
  const enrutador = useRouter()

  const correo = ref('')
  const contrasena = ref('')
  const pendiente = ref(false)
  const error = ref('')
  const segundosDeEspera = ref(0)

  /** @type {ReturnType<typeof setInterval> | undefined} */
  let cuentaAtras

  /**
   * @param {number} segundos
   * @returns {void}
   */
  function esperar(segundos) {
    clearInterval(cuentaAtras)
    segundosDeEspera.value = segundos
    cuentaAtras = setInterval(() => {
      segundosDeEspera.value -= 1
      if (segundosDeEspera.value <= 0) {
        clearInterval(cuentaAtras)
        error.value = ''
      }
    }, 1000)
  }

  /** @returns {Promise<void>} */
  async function ingresar() {
    if (pendiente.value || segundosDeEspera.value > 0) {
      return
    }
    error.value = ''
    pendiente.value = true
    try {
      await sesion.iniciar(correo.value.trim(), contrasena.value)
      contrasena.value = ''
      // La cortina lleva el nombre de la pantalla a la que se entra (sistema de diseño, Movimiento).
      const destino = enrutador.resolve(destinoSeguro(ruta.query.destino))
      const palabra = /** @type {string | undefined} */ (destino.meta.titulo) ?? 'Mesa de ayuda'
      await cubrirCon(palabra, () => enrutador.replace(destino))
    } catch (causa) {
      const problema = aErrorDeApi(causa)
      contrasena.value = ''
      if (problema.estado === 429) {
        esperar(problema.reintentarEn ?? 60)
        error.value = 'Demasiados intentos. Espere antes de volver a intentarlo.'
      } else if (problema.estado === 422) {
        // Mensaje genérico: no revela si falló el correo o la contraseña (RF-F01).
        error.value = 'El correo o la contraseña no son correctos.'
      } else {
        error.value = problema.detalle
      }
    } finally {
      pendiente.value = false
    }
  }

  onBeforeUnmount(() => clearInterval(cuentaAtras))

  const { motivoDeSalida } = storeToRefs(sesion)

  return { correo, contrasena, pendiente, error, segundosDeEspera, ingresar, motivoDeSalida }
}
