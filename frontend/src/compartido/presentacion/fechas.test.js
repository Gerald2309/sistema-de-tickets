import { describe, expect, it } from 'vitest'
import { duracionAproximada } from './fechas.js'

describe('duracionAproximada', () => {
  it('elige la unidad más legible', () => {
    expect(duracionAproximada(45 * 60)).toBe('45 min')
    expect(duracionAproximada(5 * 3600)).toBe('5 h')
    expect(duracionAproximada(2.4 * 86400)).toBe('2,4 d')
    expect(duracionAproximada(2.4 * 86400, { largo: true })).toBe('2,4 días')
  })

  it('una diferencia se escribe en la unidad de la cifra que compara', () => {
    expect(duracionAproximada(0.6 * 86400, { largo: true, unidadDe: 2.4 * 86400 })).toBe('0,6 días')
  })
})
