import { computed, reactive } from 'vue'

/**
 * Estado de la conexión de tiempo real (F-12). Lo actualiza el núcleo (nucleo/plugins/tiempo-real.js); las
 * consultas lo leen para decidir si necesitan el refresco periódico de respaldo. Toda la política vive aquí:
 * las pantallas no saben si hay WebSocket o no.
 */
export const tiempoReal = reactive({ conectado: false })

/**
 * Intervalo de refresco de una consulta: ninguno con el WebSocket conectado; el de respaldo sin él (RF-F45).
 * @param {number} milisegundos
 * @returns {import('vue').ComputedRef<number | false>}
 */
export function refrescoDeRespaldo(milisegundos) {
  return computed(() => (tiempoReal.conectado ? false : milisegundos))
}
