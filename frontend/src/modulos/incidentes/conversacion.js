/**
 * Reglas de un mensaje de la conversación, comprobadas antes de enviarlo para no subir algo que la API va a
 * rechazar. Son los mismos límites de `EscribirMensajeRequest`; una prueba de contrato los compara.
 */
export const LIMITES_DE_MENSAJE = Object.freeze({
  caracteres: 5000,
  imagenes: 4,
  megasPorImagen: 5,
  tipos: Object.freeze(['image/jpeg', 'image/png', 'image/webp']),
})

/** Lo que acepta el selector de archivos. */
export const TIPOS_ACEPTADOS = LIMITES_DE_MENSAJE.tipos.join(',')

/**
 * Separa las imágenes que se pueden adjuntar de las que no, con el motivo de cada rechazo para mostrarlo.
 * @param {number} yaAdjuntas imágenes que el mensaje ya lleva
 * @param {File[]} nuevas
 * @returns {{ aceptadas: File[], rechazos: string[] }}
 */
export function revisarImagenes(yaAdjuntas, nuevas) {
  const { imagenes, megasPorImagen, tipos } = LIMITES_DE_MENSAJE
  /** @type {File[]} */
  const aceptadas = []
  /** @type {string[]} */
  const rechazos = []

  for (const archivo of nuevas) {
    if (!tipos.includes(archivo.type)) {
      rechazos.push(`${archivo.name}: solo se admiten imágenes JPG, PNG o WebP.`)
    } else if (archivo.size > megasPorImagen * 1024 * 1024) {
      rechazos.push(`${archivo.name}: supera los ${megasPorImagen} MB.`)
    } else if (yaAdjuntas + aceptadas.length >= imagenes) {
      rechazos.push(`${archivo.name}: un mensaje lleva como máximo ${imagenes} imágenes.`)
    } else {
      aceptadas.push(archivo)
    }
  }

  return { aceptadas, rechazos }
}

/**
 * @param {number} bytes
 * @returns {string} tamaño legible, por ejemplo «1,2 MB»
 */
export function tamanoLegible(bytes) {
  const formato = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 1 })
  return bytes < 1024 * 1024 ? `${formato.format(bytes / 1024)} KB` : `${formato.format(bytes / 1024 / 1024)} MB`
}
