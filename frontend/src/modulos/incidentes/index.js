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
export { codigoDe, idDeCodigo } from './filtros.js'

/** @typedef {import('./tienda/pestanas.js').Pestana} Pestana */

/** @type {import('@/modulos/identidad/index.js').EntradaDeNavegacion[]} */
// Quien ve solo lo suyo entra a sus solicitudes; el panel y «Mi día» son la operación de soporte.
export const navegacion = [
  {
    titulo: 'Mis solicitudes',
    icono: iconos.solicitudes,
    destino: { name: 'mis-solicitudes' },
    permisos: ['ver_propios'],
    grupo: 'trabajo',
  },
  { titulo: 'Panel', icono: iconos.panel, destino: { name: 'panel' }, permisos: ['ver_dashboard'], grupo: 'trabajo' },
  {
    titulo: 'Mi día',
    icono: iconos.miDia,
    destino: { name: 'mi-dia' },
    permisos: ['cambiar_estado'],
    grupo: 'trabajo',
  },
  {
    titulo: 'Incidentes',
    icono: iconos.lista,
    destino: { name: 'incidentes' },
    permisos: ['ver_propios', 'ver_todos'],
    grupo: 'trabajo',
  },
]
