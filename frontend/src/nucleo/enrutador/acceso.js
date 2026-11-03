/**
 * Qué puede abrir cada persona (RF-F05). Cada ruta declara en `meta.permiso`:
 *   - 'publica'      solo sin sesión (el inicio de sesión);
 *   - 'abierta'      cualquiera, con sesión o sin ella (la documentación técnica);
 *   - 'autenticado'  cualquier persona con sesión;
 *   - 'cambio-de-contrasena' solo quien debe cambiar la contraseña que le puso un administrador; mientras deba
 *                    hacerlo, ninguna otra ruta con sesión se abre (la API tampoco la atiende);
 *   - una clave o una lista de claves: basta con tener una.
 * Una ruta sin `meta.permiso` se rechaza: denegar por defecto, como `ExigirAutorizacionDeclarada` del backend.
 * La autorización real la aplica la API; esto evita mostrar pantallas que van a fallar.
 */

/** @typedef {import('@/modulos/identidad/index.js').Permiso} Permiso */
/** @typedef {'publica' | 'abierta' | 'autenticado' | 'cambio-de-contrasena' | Permiso | Permiso[]} PermisoDeRuta */

/**
 * @typedef {object} EstadoDeSesion
 * @property {boolean} autenticada
 * @property {(...permisos: Permiso[]) => boolean} tieneAlguno
 * @property {boolean} [debeCambiarContrasena]
 */

/**
 * @typedef {{ tipo: 'permitir' } | { tipo: 'ingresar' } | { tipo: 'inicio' } | { tipo: 'sin-permiso' } | { tipo: 'cambiar-contrasena' }} Decision
 */

/**
 * @param {PermisoDeRuta | undefined} permiso lo que declara la ruta
 * @param {EstadoDeSesion} sesion
 * @returns {Decision}
 */
export function decidirAcceso(permiso, sesion) {
  if (permiso === 'abierta') {
    return { tipo: 'permitir' }
  }
  if (permiso === 'publica') {
    return sesion.autenticada ? { tipo: 'inicio' } : { tipo: 'permitir' }
  }
  if (!sesion.autenticada) {
    return { tipo: 'ingresar' }
  }
  if (permiso === 'cambio-de-contrasena') {
    return sesion.debeCambiarContrasena ? { tipo: 'permitir' } : { tipo: 'inicio' }
  }
  if (sesion.debeCambiarContrasena) {
    return { tipo: 'cambiar-contrasena' }
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
