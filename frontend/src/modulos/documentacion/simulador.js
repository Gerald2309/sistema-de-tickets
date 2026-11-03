/**
 * Simulador de la regla de ejemplo «Críticos a punto de vencer»: la misma decisión que toma el backend, en dos
 * tiempos. Primero la consulta (IncidentesPorDisparar: ¿le llegó el momento?) y luego el dominio (Flujo: ¿qué
 * camino toma?). Si cambia la regla del backend, cambian las condiciones de aquí y sus pruebas.
 */

// La regla: disparador sla_por_vencer al 80 % del plazo de respuesta; condición prioridad es_una_de [critica].
export const PORCENTAJE = 80

/**
 * @typedef {object} IncidenteDePrueba
 * @property {'abierto' | 'en_progreso' | 'resuelto' | 'cerrado'} estado
 * @property {'baja' | 'media' | 'alta' | 'critica'} prioridad
 * @property {boolean} respondido si ya tuvo la primera respuesta
 * @property {boolean} ejecutada si esta regla ya actuó sobre él
 * @property {number} consumo porcentaje consumido del plazo de respuesta
 */

/**
 * @typedef {object} Decision
 * @property {{ condicion: string, cumple: boolean }[]} consulta cada condición del SQL
 * @property {boolean} leToca si la consulta lo incluye en la revisión
 * @property {'si' | 'no' | null} camino el camino del flujo, si le toca
 * @property {string[]} acciones lo que hace la regla
 */

/**
 * @param {IncidenteDePrueba} incidente
 * @returns {Decision}
 */
export function decidir(incidente) {
  const consulta = [
    {
      condicion: "estado IN ('abierto', 'en_progreso')",
      cumple: ['abierto', 'en_progreso'].includes(incidente.estado),
    },
    { condicion: 'primera_respuesta_en IS NULL', cumple: !incidente.respondido },
    { condicion: 'vence_respuesta_en > ahora', cumple: incidente.consumo < 100 },
    { condicion: `consumido ≥ ${PORCENTAJE} %`, cumple: incidente.consumo >= PORCENTAJE },
    { condicion: 'NOT EXISTS ejecución', cumple: !incidente.ejecutada },
  ]
  const leToca = consulta.every(({ cumple }) => cumple)
  if (!leToca) {
    return { consulta, leToca, camino: null, acciones: [] }
  }
  const camino = incidente.prioridad === 'critica' ? 'si' : 'no'
  return {
    consulta,
    leToca,
    camino,
    acciones: camino === 'si' ? ['Avisar al rol Administrador', 'Nota interna'] : [],
  }
}
