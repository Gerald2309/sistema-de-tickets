import { existsSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import { PAGINAS } from './contenido/index.js'
import { ACCIONES, ROLES, evaluar } from './permisos.js'
import { enlaceAlCodigo } from './repositorio.js'

// La raíz del repositorio, donde empiezan las rutas que cita la documentación.
const RAIZ = join(import.meta.dirname, '..', '..', '..', '..')

/** @returns {string[]} todos los textos de las páginas */
function textos() {
  return PAGINAS.flatMap((pagina) => [
    pagina.entradilla,
    ...pagina.secciones.flatMap((seccion) =>
      seccion.bloques.flatMap((bloque) => {
        if (bloque.tipo === 'filas') return bloque.filas.flat()
        if (bloque.tipo === 'nota') return [bloque.texto]
        if (bloque.tipo === 'recorrido') return bloque.pasos.flatMap((paso) => [paso.texto, `\`${paso.archivo}\``])
        return []
      }),
    ),
  ])
}

/** @returns {string[]} las rutas de archivo que la documentación enlaza a GitHub */
function rutasEnlazadas() {
  const deTextos = textos().flatMap((texto) =>
    texto
      .split('`')
      .filter((_, i) => i % 2 === 1)
      .filter((fragmento) => fragmento.includes('/') && enlaceAlCodigo(fragmento)),
  )
  const delSimulador = Object.keys(ROLES).flatMap((rol) =>
    Object.keys(ACCIONES).flatMap((accion) =>
      evaluar(/** @type {any} */ ({ rol, accion, creador: 'yo', asignado: 'yo' })).capas.map((capa) => capa.archivo),
    ),
  )
  return [...new Set([...deTextos, ...delSimulador].filter(Boolean).map((ruta) => ruta.split(' · ')[0].trim()))]
}

describe('enlaces de la documentación al código', () => {
  it('cada archivo que se enlaza existe en el repositorio', () => {
    const rutas = rutasEnlazadas()
    expect(rutas.length).toBeGreaterThan(20)
    expect(rutas.filter((ruta) => !existsSync(join(RAIZ, ruta)))).toEqual([])
  })
})
