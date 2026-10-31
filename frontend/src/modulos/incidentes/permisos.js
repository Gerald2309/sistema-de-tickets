/**
 * Reglas de permiso que dependen del incidente, no solo del rol. Reproducen las del servidor
 * (`AlcanceIncidentes`) para no ofrecer lo que va a rechazar; la API sigue siendo quien decide (RF-F06).
 * Se aplican a incidentes que la persona ya ve, así que no repiten la regla de visibilidad.
 */

/** @typedef {import('@/modulos/identidad/index.js').Permiso} Permiso */
/** @typedef {{ tiene: (permiso: Permiso) => boolean, perfil: { id: number } | null }} Sesion */
/** @typedef {Pick<import('./tipos.js').Incidente, 'creador'>} ConCreador */

/**
 * @param {Sesion} sesion
 * @param {ConCreador} incidente
 * @returns {boolean} título, descripción, prioridad, categoría y vencimiento
 */
export function puedeEditarContenido(sesion, incidente) {
  return sesion.tiene('editar_todos') || (sesion.tiene('editar_propios') && incidente.creador.id === sesion.perfil?.id)
}

/**
 * Qué partes del incidente puede cambiar la persona: el formulario muestra solo esas (RF-F35).
 * @param {Sesion} sesion
 * @param {ConCreador} incidente
 * @returns {{ contenido: boolean, estado: boolean, asignacion: boolean }}
 */
export function partesEditables(sesion, incidente) {
  return {
    contenido: puedeEditarContenido(sesion, incidente),
    estado: sesion.tiene('cambiar_estado'),
    asignacion: sesion.tiene('reasignar'),
  }
}

/**
 * @param {Sesion} sesion
 * @param {ConCreador} incidente
 * @returns {boolean} hay al menos una parte que puede cambiar
 */
export function puedeModificar(sesion, incidente) {
  return Object.values(partesEditables(sesion, incidente)).some(Boolean)
}
