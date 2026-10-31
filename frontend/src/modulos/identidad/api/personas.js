import { http } from '@/compartido/api/cliente.js'
import { aReferencia } from '@/compartido/api/paginacion.js'

/** @typedef {import('../tipos.js').Persona} Persona */

/**
 * `GET personas` (API 31, G-05): personas para asignar o filtrar, solo id, nombre y si está activa.
 * Para asignar, solo quienes atienden incidentes y con su rol.
 * @param {{ texto?: string, ids?: number[], incluirInactivas?: boolean, paraAsignar?: boolean }} criterio
 * @param {AbortSignal} [senal]
 * @returns {Promise<Persona[]>}
 */
export async function buscarPersonas({ texto, ids, incluirInactivas, paraAsignar }, senal) {
  const { data } = await http.get('personas', {
    params: {
      q: texto || undefined,
      'ids[]': ids?.length ? ids : undefined,
      incluir_inactivas: incluirInactivas ? 1 : undefined,
      para_asignar: paraAsignar ? 1 : undefined,
    },
    signal: senal,
  })
  return data.data.map((/** @type {any} */ dto) => ({ ...aReferencia(dto), activo: dto.activo, rol: dto.rol }))
}
