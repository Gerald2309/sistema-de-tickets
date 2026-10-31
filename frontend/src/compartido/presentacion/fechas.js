/**
 * Fechas (RNF-F28): la API entrega y recibe UTC; la interfaz muestra la hora local de la persona en español de
 * Colombia. Las fechas viajan como texto ISO 8601 y solo se convierten al mostrarlas (modelo-datos-cliente §3.1).
 */

const LOCALE = 'es-CO'
const MINUTO = 60_000
const HORA = 60 * MINUTO
const DIA = 24 * HORA

const formatoFechaHora = new Intl.DateTimeFormat(LOCALE, {
  day: 'numeric',
  month: 'short',
  hour: 'numeric',
  minute: '2-digit',
})
const formatoFechaHoraAnio = new Intl.DateTimeFormat(LOCALE, {
  day: 'numeric',
  month: 'short',
  year: 'numeric',
  hour: 'numeric',
  minute: '2-digit',
})
const formatoHora = new Intl.DateTimeFormat(LOCALE, { hour: 'numeric', minute: '2-digit' })
const formatoDiaLargo = new Intl.DateTimeFormat(LOCALE, {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
  year: 'numeric',
})

/**
 * «25 oct., 6:12 a. m.»; con el año si no es el actual.
 * @param {string | null | undefined} iso
 * @param {Date} [ahora]
 * @returns {string}
 */
export function fechaHora(iso, ahora = new Date()) {
  if (!iso) {
    return '—'
  }
  const fecha = new Date(iso)
  return (fecha.getFullYear() === ahora.getFullYear() ? formatoFechaHora : formatoFechaHoraAnio).format(fecha)
}

/**
 * «hoy, 8:20 a. m.», «ayer, 5:32 p. m.» o la fecha completa.
 * @param {string | null | undefined} iso
 * @param {Date} [ahora]
 * @returns {string}
 */
export function fechaCercana(iso, ahora = new Date()) {
  if (!iso) {
    return '—'
  }
  const fecha = new Date(iso)
  const dias = Math.round((inicioDelDia(ahora) - inicioDelDia(fecha)) / DIA)
  if (dias === 0) {
    return `hoy, ${formatoHora.format(fecha)}`
  }
  if (dias === 1) {
    return `ayer, ${formatoHora.format(fecha)}`
  }
  return fechaHora(iso, ahora)
}

/**
 * @param {Date} fecha
 * @returns {number}
 */
function inicioDelDia(fecha) {
  return new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate()).getTime()
}

/**
 * Tiempo compacto hasta o desde una fecha, como en el sistema de diseño: «en 3 h», «hace 2 días», «en 45 min».
 * @param {string} iso
 * @param {Date} [ahora]
 * @returns {{ texto: string, pasado: boolean, milisegundos: number }}
 */
export function tiempoRelativo(iso, ahora = new Date()) {
  const diferencia = new Date(iso).getTime() - ahora.getTime()
  const pasado = diferencia < 0
  const absoluta = Math.abs(diferencia)
  let cantidad
  if (absoluta < HORA) {
    cantidad = `${Math.max(1, Math.round(absoluta / MINUTO))} min`
  } else if (absoluta < DIA) {
    cantidad = `${Math.round(absoluta / HORA)} h`
  } else {
    const dias = Math.round(absoluta / DIA)
    cantidad = dias === 1 ? '1 día' : `${dias} días`
  }
  return { texto: pasado ? `hace ${cantidad}` : `en ${cantidad}`, pasado, milisegundos: diferencia }
}

/**
 * Hace cuánto: «hace 12 s», «hace 3 min». Para la hora de la última actualización.
 * @param {number} instante milisegundos (Date.now())
 * @param {number} [ahora]
 * @returns {string}
 */
export function haceCuanto(instante, ahora = Date.now()) {
  const segundos = Math.max(0, Math.round((ahora - instante) / 1000))
  if (segundos < 60) {
    return `hace ${segundos} s`
  }
  const minutos = Math.round(segundos / 60)
  return minutos < 60 ? `hace ${minutos} min` : `hace ${Math.round(minutos / 60)} h`
}

/**
 * «mar, 27 oct 2026» para el selector de fecha.
 * @param {Date} fecha
 * @returns {string}
 */
export function diaLargo(fecha) {
  return formatoDiaLargo.format(fecha)
}

/**
 * Fecha local en ISO 8601 con su desfase («2026-10-27T08:00:00-05:00»), que la API convierte a UTC.
 * @param {Date} fecha
 * @returns {string}
 */
export function aIsoConDesfase(fecha) {
  const dos = (/** @type {number} */ n) => String(Math.abs(n)).padStart(2, '0')
  const desfase = -fecha.getTimezoneOffset()
  const signo = desfase >= 0 ? '+' : '-'
  return (
    `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}` +
    `T${dos(fecha.getHours())}:${dos(fecha.getMinutes())}:${dos(fecha.getSeconds())}` +
    `${signo}${dos(Math.trunc(desfase / 60))}:${dos(desfase % 60)}`
  )
}

/**
 * Principio y fin del día local de una fecha AAAA-MM-DD, para los filtros por rango (límites incluidos).
 * @param {string} dia
 * @param {'inicio' | 'fin'} extremo
 * @returns {string}
 */
export function limiteDelDia(dia, extremo) {
  const [anio, mes, fecha] = dia.split('-').map(Number)
  const instante =
    extremo === 'inicio' ? new Date(anio, mes - 1, fecha, 0, 0, 0) : new Date(anio, mes - 1, fecha, 23, 59, 59)
  return aIsoConDesfase(instante)
}
