import { http } from '@/compartido/api/cliente.js'
import { aReferencia } from '@/compartido/api/paginacion.js'

/** @typedef {import('../tipos.js').Perfil} Perfil */
/** @typedef {import('../tipos.js').CredencialDeSesion} CredencialDeSesion */

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
