import { http } from '@/compartido/api/cliente.js'
import { aReferencia } from '@/compartido/api/paginacion.js'

/** @typedef {import('../tipos.js').Perfil} Perfil */
/** @typedef {import('../tipos.js').CredencialDeSesion} CredencialDeSesion */
/** @typedef {import('../tipos.js').Sesion} Sesion */

/**
 * @param {any} dto `PerfilResource`
 * @returns {Perfil}
 */
export function aPerfil(dto) {
  return {
    id: dto.id,
    nombre: dto.name,
    correo: dto.email,
    rol: /** @type {import('../tipos.js').Referencia} */ (aReferencia(dto.rol)),
    cliente: aReferencia(dto.cliente),
    permisos: dto.permisos ?? [],
    debeCambiarContrasena: dto.debe_cambiar_contrasena ?? false,
    inicioAnterior: dto.inicio_anterior ?? null,
  }
}

/**
 * @param {any} dto `SesionResource`
 * @returns {Sesion}
 */
function aSesion(dto) {
  return {
    id: dto.id,
    ip: dto.ip,
    agente: dto.agente,
    iniciadaEn: dto.iniciada_en,
    ultimoUsoEn: dto.ultimo_uso_en,
    actual: dto.actual,
  }
}

/**
 * `POST auth/login` (API 1).
 * @param {string} correo
 * @param {string} contrasena
 * @returns {Promise<CredencialDeSesion>}
 */
export async function iniciarSesion(correo, contrasena) {
  const { data } = await http.post('auth/login', { email: correo, password: contrasena })
  return { token: data.data.token, expiraEn: data.data.expira_en ?? null }
}

/**
 * `GET me` (API 27).
 * @returns {Promise<Perfil>}
 */
export async function obtenerPerfil() {
  const { data } = await http.get('me')
  return aPerfil(data.data)
}

/**
 * `POST auth/logout` (API 2).
 * @returns {Promise<void>}
 */
export async function cerrarSesion() {
  await http.post('auth/logout')
}

/**
 * `PUT perfil/contrasena` (API 3). Al cambiarla, el servidor cierra las demás sesiones.
 * @param {string} actual
 * @param {string} nueva
 * @returns {Promise<void>}
 */
export async function cambiarContrasena(actual, nueva) {
  await http.put('perfil/contrasena', { password_actual: actual, password: nueva })
}

/**
 * `GET perfil/sesiones`: las sesiones abiertas de la persona, la más reciente primero.
 * @param {AbortSignal} [signal]
 * @returns {Promise<Sesion[]>}
 */
export async function listarSesiones(signal) {
  const { data } = await http.get('perfil/sesiones', { signal })
  return data.data.map(aSesion)
}

/**
 * `DELETE perfil/sesiones/{id}`: cierra una sesión propia en otro equipo.
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function cerrarOtraSesion(id) {
  await http.delete(`perfil/sesiones/${id}`)
}

/**
 * `DELETE perfil/sesiones`: cierra todas las sesiones propias menos la actual.
 * @returns {Promise<void>}
 */
export async function cerrarLasDemasSesiones() {
  await http.delete('perfil/sesiones')
}
