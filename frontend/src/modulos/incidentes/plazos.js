import { duracionAproximada } from '@/compartido/presentacion/fechas.js'

/** En el último 20 % del tiempo, el plazo está cerca de vencer. */
const PROPORCION_CERCANA = 0.2

/**
 * @typedef {object} EstadoDelPlazo
 * @property {'a-tiempo' | 'cerca' | 'incumplido' | 'cumplido'} estado
 * @property {string} texto
 * @property {number} avance parte del tiempo ya consumida, de 0 a 1
 */

/**
 * Cómo va un plazo del acuerdo de servicio en un instante dado.
 * @param {{ inicio: string, limite: string, cumplidoEn: string | null, ahora: Date }} plazo
 * @returns {EstadoDelPlazo}
 */
export function estadoDelPlazo({ inicio, limite, cumplidoEn, ahora }) {
  const fin = new Date(limite).getTime()

  if (cumplidoEn) {
    const exceso = new Date(cumplidoEn).getTime() - fin
    return exceso <= 0
      ? { estado: 'cumplido', texto: 'Cumplido a tiempo', avance: 1 }
      : { estado: 'incumplido', texto: `Cumplido ${duracionAproximada(exceso / 1000)} tarde`, avance: 1 }
  }

  const restante = fin - ahora.getTime()
  if (restante < 0) {
    return { estado: 'incumplido', texto: `Vencido hace ${duracionAproximada(-restante / 1000)}`, avance: 1 }
  }

  const total = Math.max(1, fin - new Date(inicio).getTime())
  const proporcion = Math.min(1, restante / total)
  return {
    estado: proporcion <= PROPORCION_CERCANA ? 'cerca' : 'a-tiempo',
    texto: `Quedan ${duracionAproximada(restante / 1000)}`,
    avance: 1 - proporcion,
  }
}
