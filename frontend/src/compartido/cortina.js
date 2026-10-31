/**
 * Cortina de página (sistema de diseño, Movimiento): franjas de marca que cubren la pantalla con una palabra y
 * se retiran. Solo al iniciar y al cerrar sesión. La pinta CortinaPagina, que se registra al montarse; sin ella
 * (por ejemplo, en pruebas) el cambio ocurre sin animación.
 */

/** @typedef {(palabra: string, alMedio: () => unknown) => Promise<void>} Reproductor */

/** @type {Reproductor | null} */
let reproductor = null
let activa = false

/**
 * @param {Reproductor | null} nuevo
 * @returns {void}
 */
export function registrarCortina(nuevo) {
  reproductor = nuevo
}

/**
 * Cubre la pantalla, ejecuta el cambio cuando está cubierta y la descubre. Si ya hay una en curso, el cambio
 * ocurre sin otra cortina.
 * @param {string} palabra lo que se lee mientras cubre (el destino)
 * @param {() => unknown} alMedio el cambio de pantalla
 * @returns {Promise<void>}
 */
export async function cubrirCon(palabra, alMedio) {
  if (!reproductor || activa) {
    await alMedio()
    return
  }
  activa = true
  try {
    await reproductor(palabra, alMedio)
  } finally {
    activa = false
  }
}
