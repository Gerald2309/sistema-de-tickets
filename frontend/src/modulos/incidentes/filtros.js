import { CATEGORIAS, ESTADOS, PRIORIDADES } from './presentacion.js'

/**
 * Filtros del listado ↔ dirección del navegador (modelo-datos-cliente §5.1, D-F11). Recargar o compartir un
 * enlace reproduce el mismo listado. Lo desconocido o inválido se descarta en silencio; el servidor valida
 * el resto.
 */

/** @typedef {import('./tipos.js').FiltrosDeIncidentes} FiltrosDeIncidentes */

export const VISTAS = /** @type {const} */ (['todos', 'para-mi', 'vencidos'])
export const ORDENES = /** @type {const} */ (['id', 'prioridad', 'estado', 'fecha_vencimiento', 'creado'])
export const TAMANOS_DE_PAGINA = /** @type {const} */ ([15, 25, 50, 100])
export const POR_PAGINA = 15

const FECHA = /^\d{4}-\d{2}-\d{2}$/

/**
 * @param {unknown} valor
 * @returns {string | undefined}
 */
function texto(valor) {
  const primero = Array.isArray(valor) ? valor[0] : valor
  return typeof primero === 'string' && primero !== '' ? primero : undefined
}

/**
 * @param {unknown} valor
 * @returns {number | undefined} entero positivo
 */
function entero(valor) {
  const numero = Number(texto(valor))
  return Number.isInteger(numero) && numero > 0 ? numero : undefined
}

/**
 * @template {string} T
 * @param {unknown} valor
 * @param {readonly T[] | Record<T, unknown>} permitidos
 * @returns {T | undefined}
 */
function unoDe(valor, permitidos) {
  const candidato = texto(valor)
  const lista = Array.isArray(permitidos) ? permitidos : Object.keys(permitidos)
  return candidato !== undefined && lista.includes(candidato) ? /** @type {T} */ (candidato) : undefined
}

/**
 * Lee los filtros de la consulta de la ruta.
 * @param {import('vue-router').LocationQuery} consulta
 * @returns {FiltrosDeIncidentes}
 */
export function filtrosDesdeConsulta(consulta) {
  /** @type {FiltrosDeIncidentes} */
  const filtros = {
    vista: unoDe(consulta.vista, VISTAS),
    q: texto(consulta.q)?.slice(0, 100),
    estado: unoDe(consulta.estado, ESTADOS),
    prioridad: unoDe(consulta.prioridad, PRIORIDADES),
    categoria: unoDe(consulta.categoria, CATEGORIAS),
    asignado: entero(consulta.asignado),
    creador: entero(consulta.creador),
    cliente: entero(consulta.cliente),
    mios: texto(consulta.mios) === '1' || undefined,
    vence: entero(consulta.vence),
    desde: FECHA.test(texto(consulta.desde) ?? '') ? texto(consulta.desde) : undefined,
    hasta: FECHA.test(texto(consulta.hasta) ?? '') ? texto(consulta.hasta) : undefined,
    sobre: unoDe(consulta.sobre, ['vencimiento', 'creacion']),
    orden: unoDe(consulta.orden, ORDENES),
    dir: unoDe(consulta.dir, ['asc', 'desc']),
    pagina: entero(consulta.pagina),
    por: TAMANOS_DE_PAGINA.find((tamano) => tamano === entero(consulta.por)),
  }
  // «Para mí» y «asignado a» no se combinan en la API (prohibits): manda la vista.
  if (filtros.vista === 'para-mi') {
    delete filtros.asignado
  }
  // «Vencidos» y «por vencer» tampoco.
  if (filtros.vista === 'vencidos') {
    delete filtros.vence
  }
  return quitarVacios(filtros)
}

/**
 * Consulta de la ruta para unos filtros: solo lo que se aparta de los valores por defecto.
 * @param {FiltrosDeIncidentes} filtros
 * @returns {Record<string, string>}
 */
export function consultaDesdeFiltros(filtros) {
  /** @type {Record<string, string>} */
  const consulta = {}
  for (const [clave, valor] of Object.entries(quitarVacios(filtros))) {
    const esPorDefecto =
      (clave === 'vista' && valor === 'todos') ||
      (clave === 'pagina' && valor === 1) ||
      (clave === 'por' && valor === POR_PAGINA) ||
      (clave === 'sobre' && valor === 'vencimiento')
    if (!esPorDefecto) {
      consulta[clave] = valor === true ? '1' : String(valor)
    }
  }
  return consulta
}

/**
 * Lee un código de incidente escrito a mano: «INC-000605», «inc605» o solo «605».
 * @param {string} texto
 * @returns {number | null} el id, o null si no es un código
 */
export function idDeCodigo(texto) {
  const coincidencia = /^\s*(?:inc-?)?0*(\d{1,9})\s*$/i.exec(texto)
  const id = coincidencia ? Number(coincidencia[1]) : 0
  return id > 0 ? id : null
}

/**
 * Código de presentación de un id (el mismo formato que entrega la API).
 * @param {number} id
 * @returns {string}
 */
export const codigoDe = (id) => `INC-${String(id).padStart(6, '0')}`

/**
 * Ruta del listado con esos filtros (para enlaces del panel, del detalle o de la paleta).
 * @param {FiltrosDeIncidentes} [filtros]
 * @returns {import('vue-router').RouteLocationRaw}
 */
export function rutaDelListado(filtros = {}) {
  return { name: 'incidentes', query: consultaDesdeFiltros(filtros) }
}

/**
 * @template {Record<string, unknown>} T
 * @param {T} objeto
 * @returns {T}
 */
function quitarVacios(objeto) {
  return /** @type {T} */ (
    Object.fromEntries(Object.entries(objeto).filter(([, valor]) => valor !== undefined && valor !== ''))
  )
}
