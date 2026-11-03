import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join, relative, sep } from 'node:path'
import { describe, expect, it } from 'vitest'

// Reglas de dependencia del frontend (arquitectura.md §3.3), como ArquitecturaTest del backend:
//   nucleo → modulos → compartido; un módulo solo usa otro por su index.js; incidentes y auditoria pueden
//   usar identidad; automatizaciones, identidad e incidentes; identidad no usa a nadie; compartido no usa nada
//   de la aplicación; solo api/ usa HTTP.

const RAIZ = join(import.meta.dirname, '..', '..', 'src')
const PUEDE_USAR = {
  incidentes: ['identidad'],
  auditoria: ['identidad'],
  automatizaciones: ['identidad', 'incidentes'],
  identidad: [],
}

/** @returns {string[]} archivos de código de src/, sin pruebas */
function archivos(carpeta = RAIZ) {
  return readdirSync(carpeta).flatMap((nombre) => {
    const ruta = join(carpeta, nombre)
    if (statSync(ruta).isDirectory()) {
      return archivos(ruta)
    }
    return /\.(js|vue)$/.test(nombre) && !nombre.endsWith('.test.js') ? [ruta] : []
  })
}

/** @returns {{ archivo: string, destino: string }[]} importaciones con alias @/ o relativas, resueltas a src/ */
function importaciones() {
  return archivos().flatMap((ruta) => {
    const archivo = relative(RAIZ, ruta).split(sep).join('/')
    const codigo = readFileSync(ruta, 'utf8')
    return [...codigo.matchAll(/(?:import|from)\s*\(?\s*['"]([^'"]+)['"]/g)]
      .map(([, origen]) => origen)
      .filter((origen) => origen.startsWith('@/') || origen.startsWith('.'))
      .map((origen) => ({
        archivo,
        destino: origen.startsWith('@/') ? origen.slice(2) : new URL(origen, `file:///${archivo}`).pathname.slice(1),
      }))
  })
}

/** @param {string} ruta */
const capa = (ruta) => ruta.split('/')[0]
/** @param {string} ruta */
const modulo = (ruta) => (ruta.startsWith('modulos/') ? ruta.split('/')[1] : null)

describe('arquitectura del frontend', () => {
  const todas = importaciones()

  it('compartido no depende de ningún módulo ni del núcleo', () => {
    const violaciones = todas.filter(
      ({ archivo, destino }) => capa(archivo) === 'compartido' && ['modulos', 'nucleo'].includes(capa(destino)),
    )
    expect(violaciones).toEqual([])
  })

  it('los módulos no dependen del núcleo', () => {
    const violaciones = todas.filter(
      ({ archivo, destino }) => capa(archivo) === 'modulos' && capa(destino) === 'nucleo',
    )
    expect(violaciones).toEqual([])
  })

  it('un módulo usa a otro solo por su index.js y solo en el sentido permitido', () => {
    const violaciones = todas.filter(({ archivo, destino }) => {
      const desde = modulo(archivo)
      const hacia = modulo(destino)
      if (!desde || !hacia || desde === hacia) {
        return false
      }
      return !PUEDE_USAR[desde]?.includes(hacia) || destino !== `modulos/${hacia}/index.js`
    })
    expect(violaciones).toEqual([])
  })

  it('el núcleo usa los módulos solo por su index.js', () => {
    const violaciones = todas.filter(
      ({ archivo, destino }) => capa(archivo) === 'nucleo' && modulo(destino) && !destino.endsWith('/index.js'),
    )
    expect(violaciones).toEqual([])
  })

  it('solo las carpetas api/ usan el cliente HTTP', () => {
    const violaciones = todas.filter(
      ({ archivo, destino }) =>
        destino === 'compartido/api/cliente.js' && !archivo.includes('/api/') && !archivo.startsWith('nucleo/'),
    )
    expect(violaciones).toEqual([])
  })

  it('las vistas no llaman a la API directamente: pasan por su composable', () => {
    const violaciones = todas.filter(
      ({ archivo, destino }) => archivo.includes('/vistas/') && destino.includes('/api/'),
    )
    expect(violaciones).toEqual([])
  })
})
