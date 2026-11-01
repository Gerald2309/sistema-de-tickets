import { http } from '@/compartido/api/cliente.js'

/** @typedef {import('../tipos.js').AcuerdoDeServicio} AcuerdoDeServicio */

/**
 * @param {any} dto `AcuerdoDeServicioResource`
 * @returns {AcuerdoDeServicio}
 */
const aAcuerdo = (dto) => ({
  horarioLaboral: dto.horario_laboral,
  plazos: Object.fromEntries(
    Object.entries(dto.plazos).map(([prioridad, /** @type {any} */ plazo]) => [
      prioridad,
      { respuestaMinutos: plazo.respuesta_minutos, resolucionMinutos: plazo.resolucion_minutos },
    ]),
  ),
})

/**
 * `GET clientes/{id}/acuerdo-de-servicio`: el propio o, si no tiene, los plazos predeterminados.
 * @param {number} clienteId
 * @param {AbortSignal} [senal]
 * @returns {Promise<AcuerdoDeServicio>}
 */
export async function obtenerAcuerdoDeServicio(clienteId, senal) {
  const { data } = await http.get(`clientes/${clienteId}/acuerdo-de-servicio`, { signal: senal })
  return aAcuerdo(data.data)
}

/**
 * `PUT clientes/{id}/acuerdo-de-servicio`.
 * @param {number} clienteId
 * @param {AcuerdoDeServicio} acuerdo
 * @returns {Promise<AcuerdoDeServicio>}
 */
export async function guardarAcuerdoDeServicio(clienteId, acuerdo) {
  const { data } = await http.put(`clientes/${clienteId}/acuerdo-de-servicio`, {
    horario_laboral: acuerdo.horarioLaboral,
    plazos: Object.fromEntries(
      Object.entries(acuerdo.plazos).map(([prioridad, plazo]) => [
        prioridad,
        { respuesta_minutos: plazo.respuestaMinutos, resolucion_minutos: plazo.resolucionMinutos },
      ]),
    ),
  })
  return aAcuerdo(data.data)
}
