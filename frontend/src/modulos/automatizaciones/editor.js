/**
 * Lo que el editor comparte con los caminos del flujo, que se pintan a sí mismos (un camino contiene otros):
 * así cada nivel no tiene que recibir y reenviar lo mismo.
 */

/** @typedef {import('./tipos.js').Paso} Paso */

/**
 * @typedef {object} ContextoDelEditor
 * @property {import('vue').Ref<Paso | 'cuando' | null>} seleccionado
 * @property {import('vue').Ref<{ lista: Paso[], k: number, condicionesAntes: number } | null>} insertarEn dónde va el bloque que se está eligiendo
 * @property {(paso: Paso | 'cuando') => void} elegir
 * @property {(lista: Paso[], i: number) => void} quitar
 * @property {(lista: Paso[], k: number, condicionesAntes: number) => void} abrirBiblioteca
 * @property {import('./tipos.js').Nombres} nombres
 * @property {import('vue').Ref<Record<string, string[]>>} errores de la API, por ruta del dato
 */

/** @type {import('vue').InjectionKey<ContextoDelEditor>} */
export const EDITOR = Symbol('editor')
