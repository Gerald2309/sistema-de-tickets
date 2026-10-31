import { http } from '@/compartido/api/cliente.js'
import { aPagina, aReferencia } from '@/compartido/api/paginacion.js'
import { limiteDelDia } from '@/compartido/presentacion/fechas.js'

/**
 * @typedef {object} RegistroDeAuditoria
 * @property {number} id
 * @property {string} accion
 * @property {import('@/compartido/api/paginacion.js').Referencia | null} usuario quién; null en un inicio fallido
 * @property {import('@/compartido/api/paginacion.js').Referencia | null} afectado
 * @property {string | null} detalle
 * @property {string | null} ip
 * @property {string} creadoEn
 */

/**
 * @typedef {object} FiltrosDeAuditoria
 * @property {string} [accion]
 * @property {number} [usuario]
 * @property {number} [afectado]
 * @property {string} [desde] AAAA-MM-DD local
 * @property {string} [hasta] AAAA-MM-DD local
 * @property {number} [pagina]
 */

/**
 * @param {any} dto `AuditoriaResource`
 * @returns {RegistroDeAuditoria}
 */
export function aRegistro(dto) {
  return {
    id: dto.id,
    accion: dto.accion,
    usuario: aReferencia(dto.usuario),
    afectado: aReferencia(dto.usuario_afectado),
    detalle: dto.detalle ?? null,
    ip: dto.ip ?? null,
    creadoEn: dto.created_at,
  }
}

/**
 * `GET auditoria` (API 25): solo lectura, lo más reciente primero. Las fechas cubren el día local completo.
 * @param {FiltrosDeAuditoria} filtros
 * @param {AbortSignal} [senal]
 * @returns {Promise<import('@/compartido/api/paginacion.js').Pagina<RegistroDeAuditoria>>}
 */
export async function listarAuditoria(filtros, senal) {
  const { data } = await http.get('auditoria', {
    params: {
      accion: filtros.accion,
      usuario_id: filtros.usuario,
      usuario_afectado_id: filtros.afectado,
      desde: filtros.desde ? limiteDelDia(filtros.desde, 'inicio') : undefined,
      hasta: filtros.hasta ? limiteDelDia(filtros.hasta, 'fin') : undefined,
      page: filtros.pagina,
      per_page: 25,
    },
    signal: senal,
  })
  return aPagina(data, aRegistro)
}
