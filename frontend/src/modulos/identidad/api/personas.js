import { http } from '@/compartido/api/cliente.js'
import { aReferencia } from '@/compartido/api/paginacion.js'

/** @typedef {{ id: number, nombre: string, activo: boolean }} Persona */

/**
 * `GET personas` (API 31, G-05): personas para asignar o filtrar, solo id, nombre y si está activa.
 * @param {{ texto?: string, ids?: number[], incluirInactivas?: boolean }} criterio
 * @param {AbortSignal} [senal]
 * @returns {Promise<Persona[]>}
 */
export async function buscarPersonas({ texto, ids, incluirInactivas }, senal) {
  const { data } = await http.get('personas', {
    params: {
      q: texto || undefined,
      'ids[]': ids?.length ? ids : undefined,
      incluir_inactivas: incluirInactivas ? 1 : undefined,
    },
    signal: senal,
  })
  return data.data.map((/** @type {any} */ dto) => ({ ...aReferencia(dto), activo: dto.activo }))
}

/** @typedef {{ id: number, nombre: string, activo: boolean, usuarios: number }} Cliente */

/**
 * `GET clientes` (API 32): empresas cliente, para filtros y administración.
 * @param {{ texto?: string, activo?: boolean, pagina?: number, porPagina?: number }} [criterio]
 * @param {AbortSignal} [senal]
 * @returns {Promise<Cliente[]>}
 */
export async function listarClientes(criterio = {}, senal) {
  const { data } = await http.get('clientes', {
    params: {
      q: criterio.texto || undefined,
      activo: criterio.activo === undefined ? undefined : Number(criterio.activo),
      page: criterio.pagina,
      per_page: criterio.porPagina ?? 100,
    },
    signal: senal,
  })
  return data.data.map((/** @type {any} */ dto) => ({
    id: dto.id,
    nombre: dto.nombre,
    activo: dto.activo,
    usuarios: dto.usuarios,
  }))
}
