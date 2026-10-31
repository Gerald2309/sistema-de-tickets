import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * @typedef {object} Notificacion
 * @property {number} id
 * @property {'exito' | 'error' | 'info'} tipo
 * @property {string} titulo
 * @property {string} [detalle]
 */

const DURACION_MS = 3600
const MAXIMO_VISIBLES = 4

/**
 * Notificaciones breves de lo que acaba de pasar (sistema de diseño, Avisos). Las pinta PilaAvisos, que las
 * anuncia a los lectores de pantalla. Es estado de interfaz compartido, por eso vive en un store.
 */
export const useNotificaciones = defineStore('notificaciones', () => {
  const lista = ref(/** @type {Notificacion[]} */ ([]))
  let siguienteId = 1

  /**
   * @param {number} id
   * @returns {void}
   */
  function descartar(id) {
    lista.value = lista.value.filter((notificacion) => notificacion.id !== id)
  }

  /**
   * @param {Notificacion['tipo']} tipo
   * @param {string} titulo
   * @param {string} [detalle]
   * @returns {number} id de la notificación
   */
  function notificar(tipo, titulo, detalle) {
    const id = siguienteId++
    lista.value = [...lista.value, { id, tipo, titulo, detalle }].slice(-MAXIMO_VISIBLES)
    setTimeout(() => descartar(id), tipo === 'error' ? DURACION_MS * 2 : DURACION_MS)
    return id
  }

  /** @typedef {(titulo: string, detalle?: string) => number} Notificar */
  return {
    lista,
    descartar,
    /** @type {Notificar} */
    exito: (titulo, detalle) => notificar('exito', titulo, detalle),
    /** @type {Notificar} */
    error: (titulo, detalle) => notificar('error', titulo, detalle),
    /** @type {Notificar} */
    info: (titulo, detalle) => notificar('info', titulo, detalle),
  }
})
