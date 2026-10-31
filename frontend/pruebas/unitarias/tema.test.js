import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import { crearVuetify } from '../../src/nucleo/plugins/vuetify.js'

// Vuetify necesita los colores del tema como valores, no como variables CSS, así que se escriben dos veces.
// Esta prueba mantiene esa copia fiel a compartido/estilos/tokens.css: cambiar un color en un solo lado falla.

const TOKENS = readFileSync(join(import.meta.dirname, '..', '..', 'src', 'compartido', 'estilos', 'tokens.css'), 'utf8')

/** @param {string} nombre */
function token(nombre) {
  const valor = TOKENS.match(new RegExp(`--${nombre}:\\s*(#[0-9a-fA-F]+);`))?.[1]
  if (!valor) {
    throw new Error(`Falta el token --${nombre}`)
  }
  const hex = valor.slice(1).toLowerCase()
  return `#${hex.length === 3 ? [...hex].map((cifra) => cifra + cifra).join('') : hex}`
}

const EQUIVALENCIAS = {
  primary: 'acento',
  secondary: 'marca-naranja',
  info: 'marca-cian',
  success: 'exito',
  warning: 'advertencia',
  error: 'error',
  background: 'fondo',
  surface: 'superficie',
  'on-background': 'texto',
  'on-surface': 'texto',
}

describe('tema de Vuetify', () => {
  it('usa los mismos colores que los tokens del sistema de diseño', () => {
    const colores = crearVuetify().theme.themes.value.psicol.colors
    for (const [color, nombre] of Object.entries(EQUIVALENCIAS)) {
      expect(String(colores[color]).toLowerCase(), color).toBe(token(nombre))
    }
  })
})
