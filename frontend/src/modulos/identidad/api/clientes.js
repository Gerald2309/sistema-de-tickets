import { http } from '@/compartido/api/cliente.js'
import { aPagina } from '@/compartido/api/paginacion.js'

/** @typedef {import('../tipos.js').Cliente} Cliente */

/** @param {any} dto `ClienteResource` */
const aCliente = (dto) =>
  /** @type {Cliente} */ ({ id: dto.id, nombre: dto.nombre, activo: dto.activo, usuarios: dto.usuarios })

/**
 * @param {{ texto?: string, activo?: boolean, pagina?: number, porPagina?: number }} criterio
 * @returns {Record<string, string | number | undefined>}
 */
const parametros = (criterio) => ({
  q: criterio.texto || undefined,
  activo: criterio.activo === undefined ? undefined : Number(criterio.activo),
  page: criterio.pagina,
  per_page: criterio.porPagina ?? 100,
})

/**
 * `GET clientes` (API 32): empresas cliente activas, para filtros y selectores (hasta 100).
 * @param {{ texto?: string, activo?: boolean }} [criterio]
 * @param {AbortSignal} [senal]
 * @returns {Promise<Cliente[]>}
 */
export async function listarClientes(criterio = {}, senal) {
  const { data } = await http.get('clientes', { params: parametros(criterio), signal: senal })
  return data.data.map(aCliente)
}

/**
 * `GET clientes` paginado, para la pantalla de empresas.
 * @param {{ texto?: string, activo?: boolean, pagina?: number, porPagina?: number }} criterio
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Pagina<Cliente>>}
 */
export async function paginarClientes(criterio, senal) {
  const { data } = await http.get('clientes', { params: parametros(criterio), signal: senal })
  return aPagina(data, aCliente)
}

/**
 * `POST clientes` (API 33).
 * @param {string} nombre
 * @returns {Promise<Cliente>}
 */
export async function crearCliente(nombre) {
  const { data } = await http.post('clientes', { nombre })
  return aCliente(data.data)
}

/**
 * `PATCH clientes/{id}` (API 34): renombrar o activar y desactivar. Las empresas no se borran.
 * @param {number} id
 * @param {{ nombre?: string, activo?: boolean }} cambios
 * @returns {Promise<Cliente>}
 */
export async function modificarCliente(id, cambios) {
  const { data } = await http.patch(`clientes/${id}`, cambios)
  return aCliente(data.data)
}
