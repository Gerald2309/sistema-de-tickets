import { describe, expect, it } from 'vitest'
import { desplazable } from './accesibilidad.js'

/**
 * @param {number} ancho
 * @param {number} visible
 */
function tabla(ancho, visible) {
  const elemento = document.createElement('div')
  const zona = document.createElement('div')
  zona.className = 'v-table__wrapper'
  zona.appendChild(document.createElement('table'))
  elemento.appendChild(zona)
  Object.defineProperty(zona, 'scrollWidth', { value: ancho })
  Object.defineProperty(zona, 'clientWidth', { value: visible })
  return { elemento, zona }
}

const montar = (/** @type {HTMLElement} */ elemento) =>
  desplazable.mounted?.(elemento, /** @type {any} */ ({ value: 'Usuarios' }), /** @type {any} */ (null), null)

describe('directiva v-desplazable', () => {
  it('una tabla que no cabe es una región con nombre que se alcanza con el teclado', () => {
    const { elemento, zona } = tabla(900, 400)
    montar(elemento)

    expect(zona.getAttribute('role')).toBe('region')
    expect(zona.getAttribute('aria-label')).toBe('Usuarios')
    expect(zona.tabIndex).toBe(0)
  })

  it('si la tabla cabe, no agrega una parada de tabulación', () => {
    const { elemento, zona } = tabla(400, 400)
    montar(elemento)

    expect(zona.hasAttribute('tabindex')).toBe(false)
  })
})
