import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { CAMPOS_DE_HISTORIAL, nombreDeCategoria, presentarEstado, presentarPrioridad } from './presentacion.js'

/**
 * Historial legible (RF-F30): cada entrada de API 10 se convierte en una frase con los nombres de la
 * interfaz, nunca con los valores internos.
 */

/**
 * El servidor guarda el vencimiento como «AAAA-MM-DD HH:MM:SS» en UTC.
 * @param {string} valor
 * @returns {string}
 */
const vencimientoLocal = (valor) => fechaHora(`${valor.replace(' ', 'T')}Z`)

/** Campos de texto largo: no caben en una línea, así que se comparan aparte. */
const TEXTOS_LARGOS = new Set(['descripcion', 'solucion'])

/**
 * @param {string | null} campo
 * @param {string | null} valor
 * @param {(id: number) => string | undefined} nombreDePersona
 * @returns {string}
 */
function valorLegible(campo, valor, nombreDePersona) {
  if (valor === null) {
    return '—'
  }
  switch (campo) {
    case 'estado':
      return presentarEstado(valor).nombre
    case 'prioridad':
      return presentarPrioridad(valor).nombre
    case 'categoria':
      return nombreDeCategoria(valor)
    case 'usuario_asignado':
      return nombreDePersona(Number(valor)) ?? 'otra persona'
    case 'fecha_vencimiento':
      return vencimientoLocal(valor)
    default:
      return valor
  }
}

/**
 * @param {import('./tipos.js').EntradaHistorial} entrada
 * @param {(id: number) => string | undefined} nombreDePersona
 * @returns {{ texto: string, detalle: string, color: string, comparacion?: { antes: string | null, despues: string | null } }}
 */
export function describirEntrada(entrada, nombreDePersona) {
  const color =
    entrada.campo === 'estado' && entrada.valorNuevo ? presentarEstado(entrada.valorNuevo).color : 'var(--acento)'
  if (entrada.evento === 'creacion') {
    return { texto: 'Creó el incidente', detalle: '', color: 'var(--estado-abierto)' }
  }
  if (entrada.evento === 'borrado') {
    return { texto: 'Eliminó el incidente', detalle: '', color: 'var(--error)' }
  }
  if (entrada.campo === 'usuario_asignado') {
    return entrada.valorNuevo === null
      ? { texto: 'Quitó la asignación', detalle: '', color }
      : { texto: `Asignó a ${valorLegible(entrada.campo, entrada.valorNuevo, nombreDePersona)}`, detalle: '', color }
  }
  const nombreDelCampo =
    CAMPOS_DE_HISTORIAL[/** @type {keyof typeof CAMPOS_DE_HISTORIAL} */ (entrada.campo)] ?? entrada.campo ?? 'Un dato'
  if (TEXTOS_LARGOS.has(entrada.campo ?? '')) {
    return {
      texto: `Cambió ${nombreDelCampo.toLowerCase()}`,
      detalle: '',
      color,
      comparacion: { antes: entrada.valorAnterior, despues: entrada.valorNuevo },
    }
  }
  return {
    texto: `Cambió ${nombreDelCampo.toLowerCase()}`,
    detalle: `${valorLegible(entrada.campo, entrada.valorAnterior, nombreDePersona)} → ${valorLegible(entrada.campo, entrada.valorNuevo, nombreDePersona)}`,
    color,
  }
}

/**
 * @param {import('./tipos.js').EntradaHistorial[]} entradas
 * @returns {number[]} personas asignadas que aparecen en el historial, para pedir sus nombres
 */
export function personasDelHistorial(entradas) {
  const ids = entradas
    .filter((entrada) => entrada.campo === 'usuario_asignado')
    .flatMap((entrada) => [entrada.valorAnterior, entrada.valorNuevo])
    .filter((valor) => valor !== null)
    .map(Number)
  return [...new Set(ids)]
}
