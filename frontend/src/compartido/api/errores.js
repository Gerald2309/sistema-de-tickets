/**
 * El único error que ven las pantallas (RNF-F22). Todo fallo de red o de la API se convierte en uno de
 * estos antes de salir del cliente HTTP, a partir del problema RFC 9457 que devuelve el backend.
 */
export class ErrorDeApi extends Error {
  /**
   * @param {object} datos
   * @param {number} datos.estado código HTTP; 0 si no hubo respuesta (sin conexión o tiempo agotado)
   * @param {string} [datos.tipo] `type` del problema, por ejemplo 'urn:incidentes:problema:validacion'
   * @param {string} datos.titulo
   * @param {string} datos.detalle mensaje para la persona, ya en español
   * @param {Record<string, string[]>} [datos.errores] errores de validación por campo (422)
   * @param {string[] | null} [datos.estadosPermitidos] estados válidos tras una transición rechazada
   * @param {number | null} [datos.reintentarEn] segundos de espera tras un 429
   */
  constructor({
    estado,
    tipo = 'about:blank',
    titulo,
    detalle,
    errores = {},
    estadosPermitidos = null,
    reintentarEn = null,
  }) {
    super(detalle)
    this.name = 'ErrorDeApi'
    this.estado = estado
    this.tipo = tipo
    this.titulo = titulo
    this.detalle = detalle
    this.errores = errores
    this.estadosPermitidos = estadosPermitidos
    this.reintentarEn = reintentarEn
  }

  /** @returns {boolean} no hubo respuesta del servidor */
  get sinConexion() {
    return this.estado === 0
  }

  /** @returns {boolean} error de validación con mensajes por campo */
  get esValidacion() {
    return this.estado === 422 && Object.keys(this.errores).length > 0
  }

  /**
   * @param {string} campo
   * @returns {string[]} mensajes de ese campo (vacío si no tiene)
   */
  erroresDe(campo) {
    return this.errores[campo] ?? []
  }
}

const MENSAJES_SIN_CUERPO = /** @type {Record<number, string>} */ ({
  401: 'Su sesión no es válida.',
  403: 'No tiene permiso para realizar esta acción.',
  404: 'No se encontró lo que buscaba.',
  429: 'Demasiadas peticiones. Intente de nuevo más tarde.',
})

/**
 * Convierte el error de Axios (o cualquier otro) en un ErrorDeApi.
 * @param {any} error
 * @returns {ErrorDeApi}
 */
export function aErrorDeApi(error) {
  if (error instanceof ErrorDeApi) {
    return error
  }

  const respuesta = error?.response
  if (!respuesta) {
    const agotado = error?.code === 'ECONNABORTED' || error?.code === 'ETIMEDOUT'
    return new ErrorDeApi({
      estado: 0,
      titulo: agotado ? 'Tiempo agotado' : 'Sin conexión',
      detalle: agotado ? 'El servidor tardó demasiado en responder.' : 'No hay conexión con el servidor.',
    })
  }

  const estado = respuesta.status
  const cuerpo = typeof respuesta.data === 'object' && respuesta.data !== null ? respuesta.data : {}
  const esProblema = typeof cuerpo.detail === 'string'

  return new ErrorDeApi({
    estado,
    tipo: esProblema ? cuerpo.type : 'about:blank',
    titulo: esProblema ? cuerpo.title : 'Error',
    // Un 5xx sin cuerpo propio (por ejemplo, del proxy) nunca muestra detalles técnicos.
    detalle: esProblema ? cuerpo.detail : (MENSAJES_SIN_CUERPO[estado] ?? 'Ocurrió un error inesperado.'),
    errores: cuerpo.errors ?? {},
    estadosPermitidos: cuerpo.estados_permitidos ?? null,
    reintentarEn: segundosDeEspera(respuesta.headers?.['retry-after']),
  })
}

/**
 * @param {unknown} valor cabecera Retry-After (segundos)
 * @returns {number | null}
 */
function segundosDeEspera(valor) {
  const segundos = Number.parseInt(String(valor ?? ''), 10)
  return Number.isFinite(segundos) && segundos >= 0 ? segundos : null
}

/**
 * El mensaje que se muestra por un error: el primero de los campos indicados, si no el primero de cualquier
 * campo (por ejemplo, el código del incidente que impidió un lote) y, si no hay ninguno, el detalle.
 * @param {unknown} causa
 * @param {string[]} [campos] campos de la API en orden de preferencia
 * @returns {string}
 */
export function mensajeDeError(causa, campos = []) {
  const problema = aErrorDeApi(causa)
  return (
    campos.flatMap((campo) => problema.erroresDe(campo))[0] ??
    Object.values(problema.errores).flat()[0] ??
    problema.detalle
  )
}
