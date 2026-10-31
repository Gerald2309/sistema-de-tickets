import { http } from '@/compartido/api/cliente.js'
import { aTramo } from '@/compartido/api/paginacion.js'

/**
 * API de «Mi día»: la agenda, la bandeja, la pizarra de notas y la marca de bloqueado. Todo es personal: la
 * API solo devuelve lo de quien pregunta.
 */

/**
 * @typedef {object} IncidenteDelPlan
 * @property {number} id
 * @property {string} codigo
 * @property {string} titulo
 * @property {string} prioridad
 * @property {string} estado
 * @property {boolean} vencido
 * @property {string} fechaVencimiento ISO
 * @property {string | null} bloqueo motivo de la marca personal de bloqueado
 */

/**
 * @typedef {object} NotaPersonal
 * @property {number} id
 * @property {string} texto
 * @property {boolean} hecha
 * @property {{ id: number, codigo: string } | null} incidente el que menciona
 */

/**
 * @typedef {object} EntradaDelPlan
 * @property {number} id
 * @property {string} fecha AAAA-MM-DD
 * @property {number} inicio minutos del día
 * @property {number} duracion minutos
 * @property {IncidenteDelPlan | null} incidente
 * @property {NotaPersonal | null} nota
 */

/** @typedef {{ incidente: IncidenteDelPlan, accion: 'mensaje' | 'cambio', momento: string }} UltimoTrabajado */

/**
 * @param {any} dto `IncidenteDelPlanResource`
 * @returns {IncidenteDelPlan}
 */
export function aIncidenteDelPlan(dto) {
  return {
    id: dto.id,
    codigo: dto.codigo,
    titulo: dto.titulo,
    prioridad: dto.prioridad,
    estado: dto.estado,
    vencido: Boolean(dto.vencido),
    fechaVencimiento: dto.fecha_vencimiento,
    bloqueo: dto.bloqueo ?? null,
  }
}

/**
 * @param {any} dto `NotaPersonalResource`
 * @returns {NotaPersonal}
 */
export function aNota(dto) {
  return { id: dto.id, texto: dto.texto, hecha: Boolean(dto.hecha), incidente: dto.incidente ?? null }
}

/**
 * @param {any} dto `EntradaDelPlanResource`
 * @returns {EntradaDelPlan}
 */
export function aEntrada(dto) {
  return {
    id: dto.id,
    fecha: dto.fecha,
    inicio: dto.inicio,
    duracion: dto.duracion,
    incidente: dto.incidente ? aIncidenteDelPlan(dto.incidente) : null,
    nota: dto.nota ? aNota(dto.nota) : null,
  }
}

/**
 * @param {string} desde AAAA-MM-DD
 * @param {string} hasta AAAA-MM-DD
 * @param {AbortSignal} [senal]
 * @returns {Promise<EntradaDelPlan[]>}
 */
export async function obtenerPlan(desde, hasta, senal) {
  const { data } = await http.get('mi-dia/plan', { params: { desde, hasta }, signal: senal })
  return data.data.map(aEntrada)
}

/**
 * Pone algo en la agenda en el primer hueco desde `inicio`; si ya estaba ese día, lo mueve.
 * @param {{ fecha: string, inicio: number, duracion?: number, incidenteId?: number, notaId?: number }} pedido
 * @returns {Promise<EntradaDelPlan>}
 */
export async function planear({ fecha, inicio, duracion, incidenteId, notaId }) {
  const { data } = await http.post('mi-dia/plan', {
    fecha,
    inicio,
    duracion,
    incidente_id: incidenteId,
    nota_id: notaId,
  })
  return aEntrada(data.data)
}

/**
 * Moverla busca el hueco desde la hora pedida; cambiar solo la duración es exacto (409 si choca).
 * @param {number} id
 * @param {{ fecha?: string, inicio?: number, duracion?: number }} cambios
 * @returns {Promise<EntradaDelPlan>}
 */
export async function reubicar(id, cambios) {
  const { data } = await http.patch(`mi-dia/plan/${id}`, cambios)
  return aEntrada(data.data)
}

/**
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function quitarDelPlan(id) {
  await http.delete(`mi-dia/plan/${id}`)
}

/**
 * @param {string} fecha
 * @param {number} desde minuto del día desde el que se buscan huecos
 * @returns {Promise<EntradaDelPlan[]>} lo que agregó
 */
export async function sugerirDia(fecha, desde) {
  const { data } = await http.post('mi-dia/sugerencia', { fecha, desde })
  return data.data.map(aEntrada)
}

/**
 * @param {string} buscar
 * @param {string | null} cursor
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Tramo<IncidenteDelPlan>>}
 */
export async function obtenerPorPlanear(buscar, cursor, senal) {
  const { data } = await http.get('mi-dia/por-planear', {
    params: { buscar: buscar || undefined, cursor: cursor ?? undefined },
    signal: senal,
  })
  return aTramo(data, aIncidenteDelPlan)
}

/**
 * @param {string} fecha hoy, en el día local
 * @param {AbortSignal} [senal]
 * @returns {Promise<IncidenteDelPlan[]>}
 */
export async function obtenerPendientes(fecha, senal) {
  const { data } = await http.get('mi-dia/pendientes', { params: { fecha }, signal: senal })
  return data.data.map(aIncidenteDelPlan)
}

/**
 * @param {AbortSignal} [senal]
 * @returns {Promise<UltimoTrabajado | null>}
 */
export async function obtenerUltimoTrabajado(senal) {
  const { data } = await http.get('mi-dia/ultimo', { signal: senal })
  return data.data ? { ...data.data, incidente: aIncidenteDelPlan(data.data.incidente) } : null
}

/**
 * @param {string} fecha hoy, en el día local
 * @param {AbortSignal} [senal]
 * @returns {Promise<NotaPersonal[]>}
 */
export async function obtenerNotas(fecha, senal) {
  const { data } = await http.get('mi-dia/notas', { params: { fecha }, signal: senal })
  return data.data.map(aNota)
}

/**
 * @param {string} texto
 * @returns {Promise<NotaPersonal>}
 */
export async function crearNota(texto) {
  const { data } = await http.post('mi-dia/notas', { texto })
  return aNota(data.data)
}

/**
 * @param {number} id
 * @param {{ texto?: string, hecha?: boolean }} campos
 * @returns {Promise<NotaPersonal>}
 */
export async function cambiarNota(id, campos) {
  const { data } = await http.patch(`mi-dia/notas/${id}`, campos)
  return aNota(data.data)
}

/**
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function borrarNota(id) {
  await http.delete(`mi-dia/notas/${id}`)
}

/**
 * @param {number} incidenteId
 * @param {string | null} motivo sin motivo, se quita la marca
 * @returns {Promise<void>}
 */
export async function marcarBloqueo(incidenteId, motivo) {
  if (motivo === null) {
    await http.delete(`mi-dia/bloqueos/${incidenteId}`)
    return
  }
  await http.put(`mi-dia/bloqueos/${incidenteId}`, { motivo })
}
