import { http } from '@/compartido/api/cliente.js'
import { aPagina, aTramo } from '@/compartido/api/paginacion.js'
import {
  aAviso,
  aCatalogos,
  aCuerpoDeIncidente,
  aEntradaHistorial,
  aIncidente,
  aIndicadoresPanel,
  aMensaje,
  aParametrosDeListado,
  aResumenPanel,
  aSimilar,
} from './adaptadores.js'

/** Una función por endpoint de incidentes (arquitectura §4). Todas las respuestas llevan `data`. */

/** @typedef {import('../tipos.js').FiltrosDeIncidentes} FiltrosDeIncidentes */
/** @typedef {import('../tipos.js').Incidente} Incidente */

/**
 * `GET incidentes` (API 5).
 * @param {FiltrosDeIncidentes} filtros
 * @param {AbortSignal} [senal] cancelación cuando cambian los filtros
 * @returns {Promise<import('@/compartido/api/paginacion.js').Pagina<Incidente>>}
 */
export async function listarIncidentes(filtros, senal) {
  const { data } = await http.get('incidentes', { params: aParametrosDeListado(filtros), signal: senal })
  return aPagina(data, aIncidente)
}

/**
 * Solo el total de un listado: pide una fila y lee `meta.total` (brecha G-04).
 * @param {FiltrosDeIncidentes} filtros
 * @param {AbortSignal} [senal]
 * @returns {Promise<number>}
 */
export async function contarIncidentes(filtros, senal) {
  const pagina = await listarIncidentes({ ...filtros, pagina: 1, por: 1 }, senal)
  return pagina.total
}

/**
 * `GET incidentes/{id}` (API 7).
 * @param {number} id
 * @param {AbortSignal} [senal]
 * @returns {Promise<Incidente>}
 */
export async function obtenerIncidente(id, senal) {
  const { data } = await http.get(`incidentes/${id}`, { signal: senal })
  return aIncidente(data.data)
}

/**
 * `POST incidentes` (API 6). Con la misma clave de idempotencia, un reintento devuelve el incidente ya creado
 * en vez de duplicarlo.
 * @param {Parameters<typeof aCuerpoDeIncidente>[0]} campos
 * @param {string} claveDeIdempotencia UUID, uno por formulario
 * @returns {Promise<Incidente>}
 */
export async function crearIncidente(campos, claveDeIdempotencia) {
  const { data } = await http.post('incidentes', aCuerpoDeIncidente(campos), {
    headers: { 'Idempotency-Key': claveDeIdempotencia },
  })
  return aIncidente(data.data)
}

/**
 * `PATCH incidentes/{id}` (API 8): solo los campos cambiados.
 * @param {number} id
 * @param {Parameters<typeof aCuerpoDeIncidente>[0]} cambios
 * @returns {Promise<Incidente>}
 */
export async function actualizarIncidente(id, cambios) {
  const { data } = await http.patch(`incidentes/${id}`, aCuerpoDeIncidente(cambios))
  return aIncidente(data.data)
}

/**
 * `POST incidentes/{id}/tomar`: queda asignado a quien lo toma.
 * @param {number} id
 * @returns {Promise<Incidente>}
 */
export async function tomarIncidente(id) {
  const { data } = await http.post(`incidentes/${id}/tomar`)
  return aIncidente(data.data)
}

/**
 * `POST incidentes/{id}/confirmacion`: quien lo reportó dice si la solución sirvió. Si sirvió, se cierra con
 * su calificación; si no, vuelve a soporte con el motivo en la conversación.
 * @param {number} id
 * @param {{ solucionado: true, calificacion: number | null } | { solucionado: false, motivo: string }} respuesta
 * @returns {Promise<Incidente>}
 */
export async function confirmarSolucion(id, respuesta) {
  const { data } = await http.post(`incidentes/${id}/confirmacion`, respuesta)
  return aIncidente(data.data)
}

/**
 * `DELETE incidentes/{id}` (API 9).
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function eliminarIncidente(id) {
  await http.delete(`incidentes/${id}`)
}

/**
 * `GET incidentes/{id}/historial` (API 10): un tramo, del cambio más reciente al más antiguo.
 * @param {number} id
 * @param {string | null} cursor null para el primer tramo
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Tramo<import('../tipos.js').EntradaHistorial>>}
 */
export async function obtenerHistorial(id, cursor, senal) {
  const { data } = await http.get(`incidentes/${id}/historial`, {
    params: { cursor: cursor ?? undefined },
    signal: senal,
  })
  return aTramo(data, aEntradaHistorial)
}

/**
 * `GET incidentes/{id}/mensajes`: un tramo de la conversación, del mensaje más reciente al anterior, sin las
 * notas internas si la persona no puede leerlas.
 * @param {number} id
 * @param {string | null} cursor null para el primer tramo (lo último)
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Tramo<import('../tipos.js').Mensaje>>}
 */
export async function obtenerConversacion(id, cursor, senal) {
  const { data } = await http.get(`incidentes/${id}/mensajes`, {
    params: { cursor: cursor ?? undefined },
    signal: senal,
  })
  return aTramo(data, aMensaje)
}

/**
 * `PUT incidentes/{id}/lectura`: deja leída la conversación hasta su último mensaje.
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function marcarConversacionLeida(id) {
  await http.put(`incidentes/${id}/lectura`)
}

/**
 * `POST incidentes/{id}/mensajes`: en formulario multiparte, porque lleva imágenes.
 * @param {number} id
 * @param {{ cuerpo: string, interno: boolean, imagenes: File[] }} mensaje
 * @returns {Promise<import('../tipos.js').Mensaje>}
 */
export async function escribirMensaje(id, { cuerpo, interno, imagenes }) {
  const formulario = new FormData()
  if (cuerpo.trim()) {
    formulario.append('cuerpo', cuerpo)
  }
  if (interno) {
    formulario.append('interno', '1')
  }
  imagenes.forEach((imagen) => formulario.append('imagenes[]', imagen))
  const { data } = await http.post(`incidentes/${id}/mensajes`, formulario, { timeout: 120_000 })
  return aMensaje(data.data)
}

/**
 * Una imagen de la conversación. Se pide con el token, así que llega como Blob: un `<img>` no podría
 * enviarlo.
 * @param {number} id
 * @param {number} mensajeId
 * @param {number} adjuntoId
 * @param {AbortSignal} [senal]
 * @returns {Promise<Blob>}
 */
export async function obtenerAdjunto(id, mensajeId, adjuntoId, senal) {
  const { data } = await http.get(`incidentes/${id}/mensajes/${mensajeId}/adjuntos/${adjuntoId}`, {
    responseType: 'blob',
    signal: senal,
  })
  return data
}

/**
 * `GET incidentes/{id}/similares` (API 24).
 * @param {number} id
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').Similar[]>}
 */
export async function obtenerSimilares(id, senal) {
  const { data } = await http.get(`incidentes/${id}/similares`, { signal: senal })
  return data.data.map(aSimilar)
}

/**
 * `PATCH incidentes/lote` (API 28): todo o nada.
 * @param {number[]} ids
 * @param {Parameters<typeof aCuerpoDeIncidente>[0]} cambios
 * @returns {Promise<Incidente[]>}
 */
export async function actualizarEnLote(ids, cambios) {
  const { data } = await http.patch('incidentes/lote', { ids, cambios: aCuerpoDeIncidente(cambios) })
  return data.data.map(aIncidente)
}

/**
 * `DELETE incidentes/lote` (API 29).
 * @param {number[]} ids
 * @returns {Promise<void>}
 */
export async function eliminarEnLote(ids) {
  await http.delete('incidentes/lote', { data: { ids } })
}

/**
 * `GET incidentes/exportar` (API 30): el archivo con los mismos filtros y orden del listado.
 * @param {FiltrosDeIncidentes} filtros
 * @param {'csv' | 'xlsx'} formato
 * @param {(progreso: number | null) => void} [alProgresar] fracción descargada, o null si no se conoce el tamaño
 * @returns {Promise<Blob>}
 */
export async function exportarIncidentes(filtros, formato, alProgresar) {
  const { pagina: _pagina, por: _por, ...sinPaginacion } = filtros
  const { data } = await http.get('incidentes/exportar', {
    params: { ...aParametrosDeListado(sinPaginacion), formato },
    responseType: 'blob',
    timeout: 120_000,
    onDownloadProgress: (evento) => alProgresar?.(evento.total ? evento.loaded / evento.total : null),
  })
  return data
}

/**
 * `GET plazo-propuesto`: los límites que tendría un incidente creado ahora, según el acuerdo de la empresa.
 * @param {string} prioridad
 * @param {AbortSignal} [senal]
 * @returns {Promise<{ venceRespuestaEn: string, fechaVencimiento: string }>}
 */
export async function obtenerPlazoPropuesto(prioridad, senal) {
  const { data } = await http.get('plazo-propuesto', { params: { prioridad }, signal: senal })
  return { venceRespuestaEn: data.data.vence_respuesta_en, fechaVencimiento: data.data.fecha_vencimiento }
}

/**
 * `GET dashboard` (API 11).
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').ResumenPanel>}
 */
export async function obtenerResumen(senal) {
  const { data } = await http.get('dashboard', { signal: senal })
  return aResumenPanel(data.data)
}

/**
 * `GET dashboard/indicadores` (API 11b): los días de la tendencia son los de la zona horaria del navegador.
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').IndicadoresPanel>}
 */
export async function obtenerIndicadores(senal) {
  const { data } = await http.get('dashboard/indicadores', {
    params: { desfase: -new Date().getTimezoneOffset() },
    signal: senal,
  })
  return aIndicadoresPanel(data.data)
}

/**
 * `GET avisos` (API 26).
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').AvisoMasivo[]>}
 */
export async function obtenerAvisos(senal) {
  const { data } = await http.get('avisos', { signal: senal })
  return data.data.map(aAviso)
}

/**
 * `GET catalogos` (API 4).
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').Catalogos>}
 */
export async function obtenerCatalogos(senal) {
  const { data } = await http.get('catalogos', { signal: senal })
  return aCatalogos(data.data)
}
