import { http } from '@/compartido/api/cliente.js'
import { aPagina } from '@/compartido/api/paginacion.js'
import {
  aAviso,
  aCatalogos,
  aCuerpoDeIncidente,
  aEntradaHistorial,
  aIncidente,
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
 * `POST incidentes` (API 6).
 * @param {Parameters<typeof aCuerpoDeIncidente>[0]} campos
 * @returns {Promise<Incidente>}
 */
export async function crearIncidente(campos) {
  const { data } = await http.post('incidentes', aCuerpoDeIncidente(campos))
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
 * `DELETE incidentes/{id}` (API 9).
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function eliminarIncidente(id) {
  await http.delete(`incidentes/${id}`)
}

/**
 * `GET incidentes/{id}/historial` (API 10).
 * @param {number} id
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').EntradaHistorial[]>}
 */
export async function obtenerHistorial(id, senal) {
  const { data } = await http.get(`incidentes/${id}/historial`, { signal: senal })
  return data.data.map(aEntradaHistorial)
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
 * `GET dashboard` (API 11).
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('../tipos.js').ResumenPanel>}
 */
export async function obtenerResumen(senal) {
  const { data } = await http.get('dashboard', { signal: senal })
  return aResumenPanel(data.data)
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
