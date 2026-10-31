import { http } from '@/compartido/api/cliente.js'

/** Una función por endpoint de roles y permisos (arquitectura §4). */

/** @typedef {import('../tipos.js').Rol} Rol */

/**
 * @param {any} dto `RolResource`
 * @returns {Rol}
 */
const aRol = (dto) => ({ id: dto.id, nombre: dto.nombre, permisos: dto.permisos ?? [], usuarios: dto.usuarios ?? 0 })

/**
 * `GET roles` (API 17).
 * @param {AbortSignal} [senal]
 * @returns {Promise<Rol[]>}
 */
export async function listarRoles(senal) {
  const { data } = await http.get('roles', { signal: senal })
  return data.data.map(aRol)
}

/**
 * `GET permisos` (API 23): las claves que existen.
 * @param {AbortSignal} [senal]
 * @returns {Promise<string[]>}
 */
export async function listarPermisos(senal) {
  const { data } = await http.get('permisos', { signal: senal })
  return data.data.map((/** @type {{ clave: string }} */ permiso) => permiso.clave)
}

/**
 * `POST roles` (API 19).
 * @param {string} nombre
 * @returns {Promise<Rol>}
 */
export async function crearRol(nombre) {
  const { data } = await http.post('roles', { nombre })
  return aRol(data.data)
}

/**
 * `PATCH roles/{id}` (API 20).
 * @param {number} id
 * @param {string} nombre
 * @returns {Promise<Rol>}
 */
export async function renombrarRol(id, nombre) {
  const { data } = await http.patch(`roles/${id}`, { nombre })
  return aRol(data.data)
}

/**
 * `DELETE roles/{id}` (API 21): la API lo impide si tiene personas.
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function eliminarRol(id) {
  await http.delete(`roles/${id}`)
}

/**
 * `PUT roles/{id}/permisos` (API 22): reemplaza la lista completa.
 * @param {number} id
 * @param {string[]} permisos
 * @returns {Promise<Rol>}
 */
export async function asignarPermisos(id, permisos) {
  const { data } = await http.put(`roles/${id}/permisos`, { permisos })
  return aRol(data.data)
}
