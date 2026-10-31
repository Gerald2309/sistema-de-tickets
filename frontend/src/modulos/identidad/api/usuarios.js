import { http } from '@/compartido/api/cliente.js'
import { aPagina, aReferencia } from '@/compartido/api/paginacion.js'

/** Una función por endpoint de usuarios (arquitectura §4). Todo exige `administrar_usuarios`. */

/** @typedef {import('../tipos.js').Usuario} Usuario */
/** @typedef {import('../tipos.js').FiltrosDeUsuarios} FiltrosDeUsuarios */

/**
 * @param {any} dto `UsuarioResource`
 * @returns {Usuario}
 */
export function aUsuario(dto) {
  return {
    id: dto.id,
    nombre: dto.name,
    correo: dto.email,
    activo: dto.activo,
    rol: /** @type {import('../tipos.js').Referencia} */ (aReferencia(dto.rol)),
    cliente: aReferencia(dto.cliente),
  }
}

/**
 * `GET usuarios` (API 12).
 * @param {FiltrosDeUsuarios} filtros
 * @param {AbortSignal} [senal]
 * @param {number} [porPagina]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Pagina<Usuario>>}
 */
export async function listarUsuarios(filtros, senal, porPagina = 15) {
  const { data } = await http.get('usuarios', {
    params: {
      q: filtros.q?.trim() || undefined,
      rol_id: filtros.rol,
      cliente_id: filtros.cliente,
      activo: filtros.estado === undefined ? undefined : Number(filtros.estado === 'activos'),
      page: filtros.pagina,
      per_page: porPagina,
    },
    signal: senal,
  })
  return aPagina(data, aUsuario)
}

/**
 * `POST usuarios` (API 13).
 * @param {{ nombre: string, correo: string, contrasena: string, rolId: number, clienteId: number | null }} datos
 * @returns {Promise<Usuario>}
 */
export async function crearUsuario({ nombre, correo, contrasena, rolId, clienteId }) {
  const { data } = await http.post('usuarios', {
    name: nombre,
    email: correo,
    password: contrasena,
    rol_id: rolId,
    cliente_id: clienteId,
  })
  return aUsuario(data.data)
}

/**
 * `PATCH usuarios/{id}` (API 15): solo los campos presentes. La contraseña es el restablecimiento por un
 * administrador; la propia se cambia en Mi cuenta.
 * @param {number} id
 * @param {{ nombre?: string, correo?: string, activo?: boolean, clienteId?: number | null, contrasena?: string }} cambios
 * @returns {Promise<Usuario>}
 */
export async function modificarUsuario(id, cambios) {
  /** @type {Record<string, unknown>} */
  const cuerpo = {}
  const nombres = /** @type {const} */ ({
    nombre: 'name',
    correo: 'email',
    activo: 'activo',
    clienteId: 'cliente_id',
    contrasena: 'password',
  })
  for (const [campo, nombreEnApi] of Object.entries(nombres)) {
    if (campo in cambios) {
      cuerpo[nombreEnApi] = /** @type {Record<string, unknown>} */ (cambios)[campo]
    }
  }
  const { data } = await http.patch(`usuarios/${id}`, cuerpo)
  return aUsuario(data.data)
}

/**
 * `PUT usuarios/{id}/rol` (API 16).
 * @param {number} id
 * @param {number} rolId
 * @returns {Promise<Usuario>}
 */
export async function asignarRol(id, rolId) {
  const { data } = await http.put(`usuarios/${id}/rol`, { rol_id: rolId })
  return aUsuario(data.data)
}
