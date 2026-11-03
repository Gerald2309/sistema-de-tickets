import { http } from '@/compartido/api/cliente.js'

/**
 * @typedef {object} AvisoPersonal un aviso de la campanita (`GET perfil/avisos`)
 * @property {string} id
 * @property {string} texto
 * @property {number | null} incidenteId el incidente al que lleva, si es sobre uno
 * @property {boolean} leido
 * @property {string} creadoEn
 */

/**
 * @param {any} dto `AvisoPersonalResource`
 * @returns {AvisoPersonal}
 */
export const aAviso = (dto) => ({
  id: dto.id,
  texto: dto.texto,
  incidenteId: dto.incidente_id ?? null,
  leido: dto.leido,
  creadoEn: dto.created_at,
})

/**
 * `GET perfil/avisos`: los más recientes y cuántos faltan por leer.
 * @param {AbortSignal} [senal]
 * @returns {Promise<{ avisos: AvisoPersonal[], sinLeer: number }>}
 */
export async function listarAvisos(senal) {
  const { data } = await http.get('perfil/avisos', { signal: senal })
  return { avisos: data.data.map(aAviso), sinLeer: data.meta.sin_leer }
}

/**
 * `PATCH perfil/avisos/{id}`.
 * @param {string} id
 * @returns {Promise<void>}
 */
export async function marcarAvisoLeido(id) {
  await http.patch(`perfil/avisos/${id}`)
}

/**
 * `POST perfil/avisos/leidos`.
 * @returns {Promise<void>}
 */
export async function marcarTodosLosAvisosLeidos() {
  await http.post('perfil/avisos/leidos')
}
