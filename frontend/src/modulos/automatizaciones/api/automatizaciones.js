import { http } from '@/compartido/api/cliente.js'
import { aReferencia } from '@/compartido/api/paginacion.js'

/** Una función por endpoint de automatizaciones (arquitectura §4). */

/** @typedef {import('../tipos.js').Automatizacion} Automatizacion */
/** @typedef {import('../tipos.js').Disparo} Disparo */
/** @typedef {import('../tipos.js').Paso} Paso */

/**
 * @param {any} dto `AutomatizacionResource`
 * @returns {Automatizacion}
 */
export function aAutomatizacion(dto) {
  return {
    id: dto.id,
    nombre: dto.nombre,
    activa: dto.activa,
    disparo: {
      disparador: dto.disparador,
      espera_minutos: dto.espera_minutos ?? null,
      porcentaje: dto.porcentaje ?? null,
      plazo: dto.plazo ?? null,
    },
    pasos: dto.pasos,
    autor: /** @type {import('@/compartido/api/paginacion.js').Referencia} */ (aReferencia(dto.autor)),
    aplicaciones: dto.aplicaciones,
    aplicacionesConError: dto.aplicaciones_con_error,
    ultimaAplicacionEn: dto.ultima_aplicacion_en ?? null,
  }
}

/**
 * `GET automatizaciones`: todas, por nombre, con su resumen de uso.
 * @param {AbortSignal} [senal]
 * @returns {Promise<Automatizacion[]>}
 */
export async function listarAutomatizaciones(senal) {
  const { data } = await http.get('automatizaciones', { signal: senal })
  return data.data.map(aAutomatizacion)
}

/**
 * `GET automatizaciones/{id}`.
 * @param {number} id
 * @param {AbortSignal} [senal]
 * @returns {Promise<Automatizacion>}
 */
export async function verAutomatizacion(id, senal) {
  const { data } = await http.get(`automatizaciones/${id}`, { signal: senal })
  return aAutomatizacion(data.data)
}

/**
 * `POST automatizaciones` o, con id, `PUT automatizaciones/{id}`: se envía completa.
 * @param {{ id?: number, nombre: string, activa: boolean, disparo: Disparo, pasos: Paso[] }} automatizacion
 * @returns {Promise<Automatizacion>}
 */
export async function guardarAutomatizacion({ id, nombre, activa, disparo, pasos }) {
  const cuerpo = { nombre, activa, ...disparo, pasos }
  const { data } = id ? await http.put(`automatizaciones/${id}`, cuerpo) : await http.post('automatizaciones', cuerpo)
  return aAutomatizacion(data.data)
}

/**
 * `PATCH automatizaciones/{id}/estado`: encenderla o apagarla.
 * @param {number} id
 * @param {boolean} activa
 * @returns {Promise<Automatizacion>}
 */
export async function cambiarEstado(id, activa) {
  const { data } = await http.patch(`automatizaciones/${id}/estado`, { activa })
  return aAutomatizacion(data.data)
}

/**
 * `DELETE automatizaciones/{id}`.
 * @param {number} id
 * @returns {Promise<void>}
 */
export async function eliminarAutomatizacion(id) {
  await http.delete(`automatizaciones/${id}`)
}

/**
 * `POST automatizaciones/prueba`: qué habrían hecho los pasos con los incidentes recientes. No cambia nada.
 * @param {Paso[]} pasos
 * @returns {Promise<import('../tipos.js').ResultadoDePrueba>}
 */
export async function probarPasos(pasos) {
  const { data } = await http.post('automatizaciones/prueba', { pasos })
  const { dias, evaluados, con_acciones: conAcciones, caminos, ejemplos } = data.data
  return { dias, evaluados, conAcciones, caminos, ejemplos }
}
