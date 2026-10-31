import { describe, expect, it, vi } from 'vitest'
import { esDeUnaVersionAnterior, olvidarRecarga, recargarHacia } from './version-nueva.js'

/** Almacenamiento en memoria, como sessionStorage. */
function almacenEnMemoria() {
  const datos = new Map()
  return {
    getItem: (/** @type {string} */ clave) => datos.get(clave) ?? null,
    setItem: (/** @type {string} */ clave, /** @type {string} */ valor) => void datos.set(clave, valor),
    removeItem: (/** @type {string} */ clave) => void datos.delete(clave),
  }
}

describe('pantallas de una versión anterior', () => {
  it('reconoce el fallo de carga de cada navegador y no confunde otros errores', () => {
    expect(
      esDeUnaVersionAnterior(
        new TypeError('Failed to fetch dynamically imported module: /assets/VistaAuditoria-abc.js'),
      ),
    ).toBe(true)
    expect(esDeUnaVersionAnterior(new TypeError('error loading dynamically imported module'))).toBe(true)
    expect(esDeUnaVersionAnterior(new TypeError('Importing a module script failed.'))).toBe(true)
    expect(esDeUnaVersionAnterior(new Error('Unable to preload CSS for /assets/VistaAuditoria-abc.css'))).toBe(true)
    expect(esDeUnaVersionAnterior(new Error('No hay conexión con el servidor.'))).toBe(false)
  })

  it('recarga hacia la ruta una sola vez, y tras una navegación correcta puede volver a hacerlo', () => {
    const almacen = almacenEnMemoria()
    const ubicacion = { assign: vi.fn() }

    expect(recargarHacia('/administracion/auditoria', almacen, ubicacion)).toBe(true)
    expect(recargarHacia('/administracion/auditoria', almacen, ubicacion)).toBe(false)
    expect(ubicacion.assign).toHaveBeenCalledTimes(1)

    olvidarRecarga(almacen)
    expect(recargarHacia('/administracion/auditoria', almacen, ubicacion)).toBe(true)
  })
})
