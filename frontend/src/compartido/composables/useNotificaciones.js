import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * @typedef {object} Notificacion
 * @property {number} id
 * @property {'exito' | 'error' | 'info'} tipo
 * @property {string} titulo
 * @property {string} [detalle]
 * @property {AccionDeAviso} [accion] lo que se puede hacer desde el aviso (por ejemplo, «Deshacer»)
 */

/** @typedef {{ texto: string, alPulsar: () => void }} AccionDeAviso */

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
   * @param {AccionDeAviso} [accion]
   * @returns {number} id de la notificación
   */
  function notificar(tipo, titulo, detalle, accion) {
    const id = siguienteId++
    lista.value = [...lista.value, { id, tipo, titulo, detalle, accion }].slice(-MAXIMO_VISIBLES)
    // Un error o un aviso con acción se quedan más: hay que alcanzar a leerlo o a pulsarla.
    setTimeout(() => descartar(id), tipo === 'error' || accion ? DURACION_MS * 2 : DURACION_MS)
    return id
  }

  /** @typedef {(titulo: string, detalle?: string, accion?: AccionDeAviso) => number} Notificar */
  return {
    lista,
    descartar,
    /** @type {Notificar} */
    exito: (titulo, detalle, accion) => notificar('exito', titulo, detalle, accion),
    /** @type {Notificar} */
    error: (titulo, detalle) => notificar('error', titulo, detalle),
    /** @type {Notificar} */
    info: (titulo, detalle, accion) => notificar('info', titulo, detalle, accion),
  }
})
