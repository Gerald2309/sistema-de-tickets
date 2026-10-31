import { reactive, ref } from 'vue'
import { aErrorDeApi } from '@/compartido/api/errores.js'

/**
 * Coloca los errores de la API bajo su campo (RF-F37). Los 422 de un campo que el formulario no muestra
 * y cualquier otro error van al mensaje general. Lo escrito nunca se borra.
 * @param {Record<string, string>} mapa campo de la API → campo del formulario, por ejemplo { password: 'nueva' }
 */
export function useErroresDeFormulario(mapa) {
  /** @type {Record<string, string[]>} */
  const errores = reactive({})
  const general = ref('')

  function limpiar() {
    for (const campo of Object.keys(errores)) {
      delete errores[campo]
    }
    general.value = ''
  }

  /**
   * @param {string} campo
   * @returns {void}
   */
  function limpiarCampo(campo) {
    delete errores[campo]
  }

  /**
   * @param {unknown} causa error de una petición
   * @returns {import('@/compartido/api/errores.js').ErrorDeApi}
   */
  function aplicar(causa) {
    limpiar()
    const problema = aErrorDeApi(causa)
    if (!problema.esValidacion) {
      general.value = problema.detalle
      return problema
    }
    const sinCampo = []
    for (const [campoDeApi, mensajes] of Object.entries(problema.errores)) {
      const campo = mapa[campoDeApi]
      if (campo) {
        errores[campo] = mensajes
      } else {
        sinCampo.push(...mensajes)
      }
    }
    general.value = sinCampo.join(' ')
    return problema
  }

  return { errores, general, aplicar, limpiar, limpiarCampo }
}
