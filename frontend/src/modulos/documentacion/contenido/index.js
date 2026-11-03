import { api } from './api.js'
import { arquitectura } from './arquitectura.js'
import { automatizaciones } from './automatizaciones.js'
import { backend } from './backend.js'
import { frontend } from './frontend.js'
import { recorrido } from './recorrido.js'

/**
 * Las páginas, en el orden del menú. La primera es la que abre /documentacion.
 * @type {import('../tipos.js').Pagina[]}
 */
export const PAGINAS = [automatizaciones, arquitectura, backend, frontend, recorrido, api]

/** Grupos del menú, en orden; el de la aplicación no lleva rótulo, como en el menú de la mesa de ayuda. */
export const GRUPOS = /** @type {const} */ ([
  { id: 'aplicacion', titulo: '' },
  { id: 'administracion', titulo: 'Administración' },
  { id: 'tecnico', titulo: 'Técnico' },
])

/** Página de entrada: la arquitectura general. */
export const PAGINA_INICIAL = arquitectura.id
