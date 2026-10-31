import { describe, expect, it } from 'vitest'
import { destinoSeguro } from './destino.js'

describe('destinoSeguro', () => {
  it('acepta rutas internas con su consulta', () => {
    expect(destinoSeguro('/incidentes?estado=abierto')).toBe('/incidentes?estado=abierto')
  })

  it.each([
    ['https://atacante.example', 'una URL completa'],
    ['//atacante.example', 'una URL sin esquema'],
    ['/\\atacante.example', 'una barra invertida'],
    ['incidentes', 'una ruta relativa'],
    [undefined, 'nada'],
    [['/panel'], 'una lista'],
  ])('rechaza %s (%s) y vuelve al inicio', (destino) => {
    expect(destinoSeguro(destino)).toBe('/')
  })
})
