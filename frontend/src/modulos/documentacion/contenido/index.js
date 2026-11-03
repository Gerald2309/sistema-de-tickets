import { api } from './api.js'
import { arquitectura } from './arquitectura.js'
import { automatizaciones } from './automatizaciones.js'
import { backend } from './backend.js'
import { calidad } from './calidad.js'
import { datos } from './datos.js'
import { decisiones } from './decisiones.js'
import { despliegue } from './despliegue.js'
import { frontend } from './frontend.js'
import { glosario } from './glosario.js'
import { incidentes } from './incidentes.js'
import { inicio } from './inicio.js'
import { instalacion } from './instalacion.js'
import { auditoria, empresas, miDia, panel, roles, usuarios } from './pantallas.js'
import { recorrido } from './recorrido.js'
import { seguridad } from './seguridad.js'
import { tiempoReal } from './tiempo-real.js'

/**
 * Las páginas, en el orden del menú.
 * @type {import('../tipos.js').Pagina[]}
 */
export const PAGINAS = [
  inicio,
  panel,
  miDia,
  incidentes,
  usuarios,
  roles,
  empresas,
  automatizaciones,
  auditoria,
  instalacion,
  arquitectura,
  backend,
  frontend,
  recorrido,
  datos,
  seguridad,
  tiempoReal,
  calidad,
  despliegue,
  decisiones,
  glosario,
  api,
]

/** Grupos del menú, en orden; el de entrada no lleva rótulo, como en el menú de la mesa de ayuda. */
export const GRUPOS = /** @type {const} */ ([
  { id: 'inicio', titulo: '' },
  { id: 'aplicacion', titulo: 'Mesa de ayuda' },
  { id: 'administracion', titulo: 'Administración' },
  { id: 'tecnico', titulo: 'Técnico' },
])

/** Página de entrada: qué es el sistema y qué hay en cada sección. */
export const PAGINA_INICIAL = inicio.id
