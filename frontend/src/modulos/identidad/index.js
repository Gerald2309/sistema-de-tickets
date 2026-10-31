/**
 * API pública del módulo de identidad: lo único que otros módulos y el núcleo pueden importar de él
 * (arquitectura §3.3; lo vigila pruebas/unitarias/arquitectura.test.js).
 */
import { iconos } from '@/compartido/iconos.js'

/**
 * Entrada del menú lateral o de la paleta de comandos.
 * @typedef {object} EntradaDeNavegacion
 * @property {string} titulo
 * @property {string} icono ruta SVG de compartido/iconos.js
 * @property {import('vue-router').RouteLocationRaw} destino
 * @property {string[]} permisos basta con tener uno
 * @property {'trabajo' | 'administracion'} grupo
 */

export { rutas, rutasPublicas } from './rutas.js'
export { useSesion } from './tienda/sesion.js'
export { clavesDeIdentidad } from './api/claves.js'
export { useBuscadorDePersonas, useEmpresas } from './composables/usePersonas.js'

/** @type {EntradaDeNavegacion[]} */
export const navegacion = [
  {
    titulo: 'Usuarios',
    icono: iconos.usuarios,
    destino: { name: 'usuarios' },
    permisos: ['administrar_usuarios'],
    grupo: 'administracion',
  },
  {
    titulo: 'Roles y permisos',
    icono: iconos.roles,
    destino: { name: 'roles' },
    permisos: ['administrar_roles'],
    grupo: 'administracion',
  },
  {
    titulo: 'Empresas',
    icono: iconos.empresa,
    destino: { name: 'empresas' },
    permisos: ['administrar_usuarios'],
    grupo: 'administracion',
  },
]
