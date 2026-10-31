/**
 * Filtros de una lista ↔ consulta de la dirección (D-F11): recargar o compartir el enlace reproduce la vista.
 * Lo que llega de la dirección no es de fiar; lo que no tiene la forma esperada se descarta.
 */

/**
 * @param {unknown} valor un parámetro de la consulta (texto o lista de textos)
 * @returns {number | undefined} entero positivo
 */
export function enteroPositivo(valor) {
  const numero = Number(Array.isArray(valor) ? valor[0] : valor)
  return Number.isInteger(numero) && numero > 0 ? numero : undefined
}

/**
 * @param {unknown} valor
 * @param {number} [maximo] longitud máxima
 * @returns {string | undefined} texto no vacío
 */
export function textoDeConsulta(valor, maximo = 100) {
  const primero = Array.isArray(valor) ? valor[0] : valor
  return typeof primero === 'string' && primero !== '' ? primero.slice(0, maximo) : undefined
}

/**
 * @template {Record<string, unknown>} T
 * @param {T} objeto
 * @returns {T} sin las claves indefinidas
 */
export function sinIndefinidos(objeto) {
  return /** @type {T} */ (
    Object.fromEntries(Object.entries(objeto).filter(([, valor]) => valor !== undefined && valor !== ''))
  )
}

/**
 * Consulta de la dirección para unos filtros: solo lo definido, como texto, sin la página 1.
 * @param {Record<string, unknown>} filtros
 * @returns {Record<string, string>}
 */
export function aConsultaDeRuta(filtros) {
  return Object.fromEntries(
    Object.entries(sinIndefinidos(filtros))
      .filter(([clave, valor]) => !(clave === 'pagina' && valor === 1))
      .map(([clave, valor]) => [clave, String(valor)]),
  )
}

/**
 * Aplica cambios a unos filtros: cualquier cambio que no sea de página vuelve a la primera.
 * @template {Record<string, unknown>} T
 * @param {T} filtros
 * @param {Partial<T>} cambios
 * @returns {T}
 */
export function conCambios(filtros, cambios) {
  const nuevos = { ...filtros, ...cambios }
  if (!('pagina' in cambios)) {
    delete nuevos.pagina
  }
  return nuevos
}
