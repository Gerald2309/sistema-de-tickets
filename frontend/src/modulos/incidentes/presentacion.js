import { iconos } from '@/compartido/iconos.js'

/**
 * Presentación de los valores del catálogo (modelo-datos-cliente.md §6). Los valores son los de la API
 * (`GET /catalogos`); aquí solo se les da nombre, ícono y color. Es la única tabla de traducción del cliente:
 * una prueba falla si la API trae un valor sin presentación.
 */

/** @typedef {{ nombre: string, color: string }} PresentacionDeEstado */
/** @typedef {{ nombre: string, icono: string, color: string }} PresentacionDePrioridad */

/** @type {Readonly<Record<string, PresentacionDeEstado>>} */
export const ESTADOS = Object.freeze({
  abierto: { nombre: 'Abierto', color: 'var(--estado-abierto)' },
  en_progreso: { nombre: 'En progreso', color: 'var(--estado-en-progreso)' },
  resuelto: { nombre: 'Resuelto', color: 'var(--estado-resuelto)' },
  cerrado: { nombre: 'Cerrado', color: 'var(--estado-cerrado)' },
})

/** Orden lógico de las prioridades en la interfaz: de la más urgente a la menos. */
/** @type {Readonly<Record<string, PresentacionDePrioridad>>} */
export const PRIORIDADES = Object.freeze({
  critica: { nombre: 'Crítica', icono: iconos.prioridadCritica, color: 'var(--prioridad-critica)' },
  alta: { nombre: 'Alta', icono: iconos.prioridadAlta, color: 'var(--prioridad-alta)' },
  media: { nombre: 'Media', icono: iconos.prioridadMedia, color: 'var(--prioridad-media)' },
  baja: { nombre: 'Baja', icono: iconos.prioridadBaja, color: 'var(--prioridad-baja)' },
})

/** @type {Readonly<Record<string, string>>} */
export const CATEGORIAS = Object.freeze({
  red: 'Red',
  correo: 'Correo',
  hardware: 'Hardware',
  software: 'Software',
  accesos: 'Accesos',
  seguridad: 'Seguridad',
  telefonia: 'Telefonía',
  otros: 'Otros',
})

/** Campos que registra el historial (CampoHistorial del backend). */
/** @type {Readonly<Record<string, string>>} */
export const CAMPOS_DE_HISTORIAL = Object.freeze({
  titulo: 'Título',
  estado: 'Estado',
  prioridad: 'Prioridad',
  usuario_asignado: 'Asignado',
  fecha_vencimiento: 'Vencimiento',
  categoria: 'Categoría',
})

/**
 * @param {string} estado
 * @returns {PresentacionDeEstado}
 */
export function presentarEstado(estado) {
  return ESTADOS[estado] ?? { nombre: estado, color: 'var(--estado-cerrado)' }
}

/**
 * @param {string} prioridad
 * @returns {PresentacionDePrioridad}
 */
export function presentarPrioridad(prioridad) {
  return PRIORIDADES[prioridad] ?? { nombre: prioridad, icono: iconos.prioridadMedia, color: 'var(--prioridad-baja)' }
}

/**
 * @param {string} categoria
 * @returns {string}
 */
export function nombreDeCategoria(categoria) {
  return CATEGORIAS[categoria] ?? categoria
}
