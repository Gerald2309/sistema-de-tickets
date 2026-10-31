import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { ORDENES } from './filtros.js'
import {
  CAMPOS_DE_HISTORIAL,
  CATEGORIAS,
  ESTADOS,
  PRIORIDADES,
  nombreDeCategoria,
  nombreDelPaso,
  pasoDeLaSolicitud,
  presentarEstado,
} from './presentacion.js'

/**
 * Contrato con el backend: los valores de cada enum de PHP deben tener su presentación aquí. Si la API agrega
 * un estado, una categoría o un campo del historial, esta prueba falla antes de que llegue a la interfaz.
 * @param {string} archivo enum de backend/app/Incidentes/Dominio
 * @returns {string[]}
 */
function valoresDelEnum(archivo) {
  // Vitest corre desde la carpeta del frontend; el backend está al lado, en el mismo repositorio.
  const ruta = resolve(process.cwd(), '../backend/app/Incidentes/Dominio', archivo)
  return [...readFileSync(ruta, 'utf8').matchAll(/case \w+ = '([^']+)';/g)].map(([, valor]) => valor)
}

describe('presentación del catálogo', () => {
  it.each([
    ['Estado.php', ESTADOS],
    ['Prioridad.php', PRIORIDADES],
    ['Categoria.php', CATEGORIAS],
    ['CampoHistorial.php', CAMPOS_DE_HISTORIAL],
  ])('cada valor de %s tiene presentación y no sobra ninguna', (archivo, presentacion) => {
    const valores = valoresDelEnum(archivo)

    expect(valores.length).toBeGreaterThan(0)
    expect(Object.keys(presentacion).sort()).toEqual([...valores].sort())
  })

  it('los órdenes del listado son los que acepta la API', () => {
    expect([...ORDENES].sort()).toEqual(valoresDelEnum('CampoOrden.php').sort())
  })

  it('un valor desconocido se muestra tal cual en lugar de romper la pantalla', () => {
    expect(presentarEstado('suspendido').nombre).toBe('suspendido')
    expect(nombreDeCategoria('impresoras')).toBe('impresoras')
  })
})

describe('camino de una solicitud', () => {
  it('cuenta el paso según el estado; recibida y ya tomada, va en atención', () => {
    const casos = [
      [{ estado: 'abierto', asignado: null }, 0, 'Esperando a soporte'],
      [{ estado: 'abierto', asignado: { id: 7, nombre: 'Camila Ríos' } }, 1, 'En atención'],
      [{ estado: 'en_progreso', asignado: { id: 7, nombre: 'Camila Ríos' } }, 1, 'En atención'],
      [{ estado: 'resuelto', asignado: null }, 2, 'Resuelta'],
      [{ estado: 'cerrado', asignado: null }, 3, 'Cerrada'],
    ]
    for (const [incidente, paso, nombre] of casos) {
      expect(pasoDeLaSolicitud(/** @type {any} */ (incidente))).toBe(paso)
      expect(nombreDelPaso(/** @type {any} */ (incidente))).toBe(nombre)
    }
  })
})
