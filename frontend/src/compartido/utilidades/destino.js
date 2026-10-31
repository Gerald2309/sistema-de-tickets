/**
 * Destino al que volver tras iniciar sesión. Solo rutas internas: nunca una URL completa recibida por
 * parámetro (redirección abierta, OWASP).
 * @param {unknown} destino
 * @returns {string}
 */
export function destinoSeguro(destino) {
  if (typeof destino !== 'string' || !destino.startsWith('/') || destino.startsWith('//') || destino.includes('\\')) {
    return '/'
  }
  return destino
}
