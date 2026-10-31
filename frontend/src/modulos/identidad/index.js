/**
 * API pública del módulo de identidad: lo único que otros módulos y el núcleo pueden importar de él
 * (arquitectura §3.3; lo vigila pruebas/unitarias/arquitectura.test.js).
 */

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
export { clavesDeIdentidad, useBuscadorDePersonas, useEmpresas } from './composables/usePersonas.js'

/** @type {EntradaDeNavegacion[]} */
export const navegacion = []
