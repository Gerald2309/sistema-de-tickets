/**
 * Qué puede abrir cada persona (RF-F05). Cada ruta declara en `meta.permiso`:
 *   - 'publica'      solo sin sesión (el inicio de sesión);
 *   - 'autenticado'  cualquier persona con sesión;
 *   - una clave o una lista de claves: basta con tener una.
 * Una ruta sin `meta.permiso` se rechaza: denegar por defecto, como `ExigirAutorizacionDeclarada` del backend.
 * La autorización real la aplica la API; esto evita mostrar pantallas que van a fallar.
 */

/** @typedef {import('@/modulos/identidad/index.js').Permiso} Permiso */
/** @typedef {'publica' | 'autenticado' | Permiso | Permiso[]} PermisoDeRuta */

/**
 * @typedef {object} EstadoDeSesion
 * @property {boolean} autenticada
 * @property {(...permisos: Permiso[]) => boolean} tieneAlguno
 */

/**
 * @typedef {{ tipo: 'permitir' } | { tipo: 'ingresar' } | { tipo: 'inicio' } | { tipo: 'sin-permiso' }} Decision
 */

/**
 * @param {PermisoDeRuta | undefined} permiso lo que declara la ruta
 * @param {EstadoDeSesion} sesion
 * @returns {Decision}
 */
export function decidirAcceso(permiso, sesion) {
  if (permiso === 'publica') {
    return sesion.autenticada ? { tipo: 'inicio' } : { tipo: 'permitir' }
  }
  if (!sesion.autenticada) {
    return { tipo: 'ingresar' }
  }
  if (permiso === 'autenticado') {
    return { tipo: 'permitir' }
  }
  const requeridos = Array.isArray(permiso) ? permiso : permiso ? [permiso] : []
  if (requeridos.length === 0) {
    return { tipo: 'sin-permiso' }
  }
  return sesion.tieneAlguno(...requeridos) ? { tipo: 'permitir' } : { tipo: 'sin-permiso' }
}
