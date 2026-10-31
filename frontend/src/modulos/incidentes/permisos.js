/**
 * Reglas de permiso que dependen del incidente, no solo del rol. Reproducen las del servidor
 * (`AlcanceIncidentes`) para no ofrecer lo que va a rechazar; la API sigue siendo quien decide (RF-F06).
 * Se aplican a incidentes que la persona ya ve, así que no repiten la regla de visibilidad.
 *
 * Un incidente lo trabaja solo quien lo tiene asignado: primero se toma y después se modifica. El
 * solicitante corrige la clasificación del suyo mientras nadie lo ha tomado. El título y la descripción quedan
 * fijos al crearlo: son las palabras de quien lo reportó.
 */

/** @typedef {import('@/modulos/identidad/index.js').Permiso} Permiso */
/** @typedef {{ tiene: (permiso: Permiso) => boolean, perfil: { id: number } | null }} Sesion */
/** @typedef {Pick<import('./tipos.js').Incidente, 'creador' | 'asignado'>} ConParticipantes */
/** @typedef {{ clasificacion: boolean, estado: boolean, asignacion: boolean }} PartesEditables */

/** @type {Readonly<PartesEditables>} */
export const NADA_EDITABLE = Object.freeze({ clasificacion: false, estado: false, asignacion: false })

/**
 * @param {Sesion} sesion
 * @param {ConParticipantes} incidente
 * @returns {boolean}
 */
const loAtiende = (sesion, incidente) => incidente.asignado !== null && incidente.asignado.id === sesion.perfil?.id

/**
 * @param {Sesion} sesion
 * @param {ConParticipantes} incidente
 * @returns {boolean}
 */
const loCreo = (sesion, incidente) => incidente.creador.id === sesion.perfil?.id

/**
 * @param {Sesion} sesion
 * @param {ConParticipantes & Pick<import('./tipos.js').Incidente, 'estado'>} incidente
 * @returns {boolean} está libre y la persona atiende incidentes
 */
export function puedeTomar(sesion, incidente) {
  return sesion.tiene('cambiar_estado') && incidente.asignado === null && incidente.estado !== 'cerrado'
}

/**
 * @param {Sesion} sesion
 * @param {ConParticipantes} incidente
 * @returns {boolean} prioridad, categoría y vencimiento
 */
function puedeClasificar(sesion, incidente) {
  return (
    (sesion.tiene('editar_todos') && loAtiende(sesion, incidente)) ||
    (sesion.tiene('editar_propios') && loCreo(sesion, incidente) && incidente.asignado === null)
  )
}

/**
 * Quitar la asignación devuelve el incidente a la cola: solo quien reparte el trabajo.
 * @param {Sesion} sesion
 * @returns {boolean}
 */
export function puedeDejarSinAsignar(sesion) {
  return sesion.tiene('reasignar')
}

/**
 * Si la solución sirvió lo dice quien reportó el incidente, mientras está resuelto.
 * @param {Sesion} sesion
 * @param {ConParticipantes & Pick<import('./tipos.js').Incidente, 'estado'>} incidente
 * @returns {boolean}
 */
export function puedeConfirmarSolucion(sesion, incidente) {
  return incidente.estado === 'resuelto' && loCreo(sesion, incidente)
}

/**
 * La conversación es entre quien reportó el incidente y quien lo atiende; cerrado, queda de solo lectura.
 * @param {Sesion} sesion
 * @param {ConParticipantes & Pick<import('./tipos.js').Incidente, 'estado'>} incidente
 * @returns {boolean}
 */
export function puedeConversar(sesion, incidente) {
  return incidente.estado !== 'cerrado' && (loCreo(sesion, incidente) || loAtiende(sesion, incidente))
}

/**
 * Las notas internas las escribe quien atiende el incidente; nunca las lee el solicitante.
 * @param {Sesion} sesion
 * @param {ConParticipantes} incidente
 * @returns {boolean}
 */
export function puedeEscribirNotaInterna(sesion, incidente) {
  return sesion.tiene('ver_todos') && loAtiende(sesion, incidente)
}

/**
 * Qué partes del incidente puede cambiar la persona (RF-F35), desde el detalle.
 * @param {Sesion} sesion
 * @param {ConParticipantes} incidente
 * @returns {PartesEditables}
 */
export function partesEditables(sesion, incidente) {
  return {
    clasificacion: puedeClasificar(sesion, incidente),
    estado: sesion.tiene('cambiar_estado') && loAtiende(sesion, incidente),
    asignacion: sesion.tiene('reasignar') || loAtiende(sesion, incidente),
  }
}
