/**
 * Reglas de presentación de «Mi día», sin Vue: días locales, horas de la agenda y agrupación por urgencia.
 * Las horas van en minutos del día (600 son las 10:00), como las guarda la API.
 */

/** La jornada de la agenda (la misma de la API, Jornada.php). */
export const JORNADA = Object.freeze({
  // Lo que se muestra de entrada; desde TEMPRANO se puede desplegar.
  inicio: 8 * 60,
  temprano: 6 * 60,
  fin: 18 * 60,
  paso: 30,
  duraciones: [30, 60, 90, 120, 180],
  capacidadHabitual: 5,
})

const DIA_MS = 24 * 60 * 60 * 1000
const URGENCIA = [
  { clave: 'hoy', nombre: 'Vencen hoy' },
  { clave: 'semana', nombre: 'Esta semana' },
  { clave: 'despues', nombre: 'Más adelante' },
]

/**
 * @param {Date} fecha
 * @returns {string} el día local AAAA-MM-DD
 */
export function diaLocal(fecha) {
  const dos = (/** @type {number} */ n) => String(n).padStart(2, '0')
  return `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}`
}

/**
 * @param {string} dia AAAA-MM-DD
 * @returns {Date} ese día a medianoche local
 */
export function aFecha(dia) {
  const [anio, mes, numero] = dia.split('-').map(Number)
  return new Date(anio, mes - 1, numero)
}

/**
 * @param {string} dia
 * @param {number} dias
 * @returns {string}
 */
export function sumarDias(dia, dias) {
  const fecha = aFecha(dia)
  fecha.setDate(fecha.getDate() + dias)
  return diaLocal(fecha)
}

/**
 * @param {string} dia
 * @returns {string} el lunes de esa semana
 */
export function lunesDe(dia) {
  return sumarDias(dia, -((aFecha(dia).getDay() + 6) % 7))
}

/**
 * @param {string} dia
 * @returns {string[]} lunes a domingo de la semana de ese día
 */
export function semanaDe(dia) {
  const lunes = lunesDe(dia)
  return Array.from({ length: 7 }, (_, n) => sumarDias(lunes, n))
}

/**
 * @param {string} dia
 * @returns {boolean} es sábado o domingo
 */
export function esFinDeSemana(dia) {
  return [0, 6].includes(aFecha(dia).getDay())
}

/**
 * @param {string} dia
 * @returns {string[]} las 42 casillas (6 semanas, de lunes) de la cuadrícula del mes de ese día
 */
export function cuadriculaDelMes(dia) {
  const primero = lunesDe(`${dia.slice(0, 7)}-01`)
  return Array.from({ length: 42 }, (_, n) => sumarDias(primero, n))
}

/**
 * @param {Date} fecha
 * @returns {number} minutos desde la medianoche local
 */
export function minutoDelDia(fecha) {
  return fecha.getHours() * 60 + fecha.getMinutes()
}

/**
 * @param {number} minutos
 * @returns {string} 10:30
 */
export function hora(minutos) {
  return `${Math.floor(minutos / 60)}:${String(minutos % 60).padStart(2, '0')}`
}

/**
 * @param {number} minutos
 * @returns {string} 30 min, 1 h, 1,5 h
 */
export function duracionLegible(minutos) {
  return minutos < 60 ? `${minutos} min` : `${String(minutos / 60).replace('.', ',')} h`
}

/**
 * @param {number} actual
 * @returns {number} la duración que sigue al pulsarla (vuelve a la primera)
 */
export function siguienteDuracion(actual) {
  const indice = JORNADA.duraciones.indexOf(actual)
  return JORNADA.duraciones[(indice + 1) % JORNADA.duraciones.length]
}

/**
 * @param {number} minutos
 * @returns {number} redondeado hacia abajo al tramo de media hora
 */
export function aTramo(minutos) {
  return Math.floor(minutos / JORNADA.paso) * JORNADA.paso
}

/**
 * Lo resuelto (incidente resuelto o cerrado, nota hecha) ya no se trabaja: se ve atenuado.
 * @param {import('./api/mi-dia.js').EntradaDelPlan} entrada
 * @returns {boolean}
 */
export function estaTerminada(entrada) {
  return entrada.nota ? entrada.nota.hecha : ['resuelto', 'cerrado'].includes(entrada.incidente?.estado ?? '')
}

/**
 * @param {import('./api/mi-dia.js').EntradaDelPlan} entrada
 * @returns {boolean} se puede trabajar ahora: ni terminada ni bloqueada
 */
export function esTrabajable(entrada) {
  return !estaTerminada(entrada) && !entrada.incidente?.bloqueo
}

/**
 * «Ahora» es lo trabajable que no ha terminado de pasar a esta hora; «siguiente», lo trabajable que viene después.
 * @param {import('./api/mi-dia.js').EntradaDelPlan[]} entradas del día, en orden
 * @param {number} minutoActual
 * @returns {{ ahora: import('./api/mi-dia.js').EntradaDelPlan | null, siguiente: import('./api/mi-dia.js').EntradaDelPlan | null }}
 */
export function ahoraYSiguiente(entradas, minutoActual) {
  const trabajables = entradas.filter(esTrabajable)
  const ahora = trabajables.find((entrada) => entrada.inicio + entrada.duracion > minutoActual) ?? null
  const desde = ahora ? ahora.inicio : minutoActual
  const siguiente = trabajables.find((entrada) => entrada !== ahora && entrada.inicio >= desde) ?? null
  return { ahora, siguiente }
}

/**
 * Agrupa lo por planear (ya viene ordenado por vencimiento) en vencen hoy, esta semana y más adelante.
 * @template {{ vencido: boolean, fechaVencimiento: string }} T
 * @param {T[]} incidentes
 * @param {Date} ahora
 * @returns {{ clave: string, nombre: string, elementos: T[] }[]} solo los grupos con algo
 */
export function agruparPorUrgencia(incidentes, ahora) {
  const finDeHoy = aFecha(diaLocal(ahora)).getTime() + DIA_MS
  /** @param {T} incidente */
  const grupo = (incidente) => {
    const vence = new Date(incidente.fechaVencimiento).getTime()
    if (incidente.vencido || vence < finDeHoy) {
      return 'hoy'
    }
    return vence < finDeHoy + 6 * DIA_MS ? 'semana' : 'despues'
  }
  return URGENCIA.map(({ clave, nombre }) => ({
    clave,
    nombre,
    elementos: incidentes.filter((incidente) => grupo(incidente) === clave),
  })).filter((grupoDeUrgencia) => grupoDeUrgencia.elementos.length > 0)
}

/** Lo que se arrastra en «Mi día»: algo de la bandeja o de la pizarra, o una entrada que ya está en la agenda. */
export const TIPO_DE_ARRASTRE = 'application/x-mi-dia'

/** @typedef {{ tipo: 'incidente' | 'nota' | 'entrada', id: number }} Arrastrado */

/**
 * @param {DragEvent} evento
 * @param {Arrastrado} arrastrado
 * @returns {void}
 */
export function empezarArrastre(evento, arrastrado) {
  evento.dataTransfer?.setData(TIPO_DE_ARRASTRE, JSON.stringify(arrastrado))
  if (evento.dataTransfer) {
    evento.dataTransfer.effectAllowed = 'move'
  }
}

/**
 * @param {DragEvent} evento
 * @returns {Arrastrado | null} lo arrastrado, si viene de «Mi día»
 */
export function leerArrastre(evento) {
  try {
    const datos = JSON.parse(evento.dataTransfer?.getData(TIPO_DE_ARRASTRE) || 'null')
    return datos && ['incidente', 'nota', 'entrada'].includes(datos.tipo) && Number.isInteger(datos.id) ? datos : null
  } catch {
    return null
  }
}

/**
 * Color del papel de una nota: se reparte por su id, así una nota conserva su color.
 * @param {number} id
 * @returns {'cian' | 'naranja' | 'verde'}
 */
export function colorDeNota(id) {
  return /** @type {const} */ (['cian', 'naranja', 'verde'])[id % 3]
}
