/**
 * API pública del módulo de incidentes: lo único que otros módulos y el núcleo pueden importar de él
 * (arquitectura §3.3; lo vigila pruebas/unitarias/arquitectura.test.js).
 */
import { iconos } from '@/compartido/iconos.js'

export { rutas, guardaDePestanas } from './rutas.js'
export { usePestanas, MAXIMO_DE_PESTANAS } from './tienda/pestanas.js'
export { claves as clavesDeIncidentes } from './api/claves.js'
export { presentarEstado } from './presentacion.js'
export { invalidarIncidentes } from './composables/useInvalidacion.js'

/** @typedef {import('./tienda/pestanas.js').Pestana} Pestana */

/** @type {import('@/modulos/identidad/index.js').EntradaDeNavegacion[]} */
export const navegacion = [
  { titulo: 'Panel', icono: iconos.panel, destino: { name: 'panel' }, permisos: ['ver_dashboard'], grupo: 'trabajo' },
  {
    titulo: 'Incidentes',
    icono: iconos.lista,
    destino: { name: 'incidentes' },
    permisos: ['ver_propios', 'ver_todos'],
    grupo: 'trabajo',
  },
]
