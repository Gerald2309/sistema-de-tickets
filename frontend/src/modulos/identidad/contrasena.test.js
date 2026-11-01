import { describe, expect, it } from 'vitest'
import { cumpleRequisitos } from './contrasena.js'

describe('requisitos de una contraseña nueva', () => {
  it.each([
    ['muy corta', 'Ab1-corta'],
    ['sin mayúscula', 'una-clave-larga-2026'],
    ['sin minúscula', 'UNA-CLAVE-LARGA-2026'],
    ['sin número', 'Una-Clave-Bien-Larga'],
    ['sin símbolo', 'UnaClaveBienLarga2026'],
  ])('rechaza una contraseña %s', (_, contrasena) => {
    expect(cumpleRequisitos(contrasena)).toBe(false)
  })

  it('acepta una que cumple todo, también con letras acentuadas o espacios', () => {
    expect(cumpleRequisitos('Psicol-Mesa-De-Ayuda-2026')).toBe(true)
    expect(cumpleRequisitos('Mi contraseña Ñandú 2026')).toBe(true)
  })
})
