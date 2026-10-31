import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * @typedef {object} PedidoDeConfirmacion
 * @property {string} titulo
 * @property {string} texto
 * @property {string} accion texto del botón que confirma («Eliminar»)
 * @property {boolean} [peligrosa] acción destructiva: botón en rojo
 */

/**
 * Confirmación antes de una acción destructiva (sistema de diseño, Avisos y diálogos). Una sola ventana para
 * toda la aplicación (DialogoConfirmacion); quien la pide espera la respuesta:
 *   if (await confirmacion.pedir({ titulo, texto, accion: 'Eliminar', peligrosa: true })) { … }
 */
export const useConfirmacion = defineStore('confirmacion', () => {
  const pedido = ref(/** @type {PedidoDeConfirmacion | null} */ (null))
  /** @type {((respuesta: boolean) => void) | null} */
  let responder = null

  /**
   * @param {PedidoDeConfirmacion} datos
   * @returns {Promise<boolean>}
   */
  function pedir(datos) {
    responder?.(false)
    pedido.value = datos
    return new Promise((resolver) => (responder = resolver))
  }

  /**
   * @param {boolean} respuesta
   * @returns {void}
   */
  function contestar(respuesta) {
    responder?.(respuesta)
    responder = null
    pedido.value = null
  }

  return { pedido, pedir, contestar }
})
