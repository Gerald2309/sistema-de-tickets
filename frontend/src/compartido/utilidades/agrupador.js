/**
 * Junta los ids que llegan en un intervalo corto y los entrega una sola vez, sin repetidos. Sirve para atender
 * varios avisos de tiempo real con una sola tanda de peticiones.
 * @param {number} espera milisegundos desde el primer id de la tanda
 * @param {(ids: number[]) => void} alVaciar
 */
export function crearAgrupador(espera, alVaciar) {
  /** @type {Set<number>} */
  let pendientes = new Set()
  /** @type {ReturnType<typeof setTimeout> | undefined} */
  let temporizador

  function vaciar() {
    const ids = [...pendientes]
    pendientes = new Set()
    temporizador = undefined
    alVaciar(ids)
  }

  return {
    /**
     * @param {unknown[]} ids lo que no sea un entero se descarta
     * @returns {void}
     */
    agregar(ids) {
      ids.filter((id) => Number.isInteger(id)).forEach((id) => pendientes.add(/** @type {number} */ (id)))
      temporizador ??= setTimeout(vaciar, espera)
    },
    /** @returns {void} descarta lo pendiente sin entregarlo */
    cancelar() {
      clearTimeout(temporizador)
      temporizador = undefined
      pendientes = new Set()
    },
  }
}
