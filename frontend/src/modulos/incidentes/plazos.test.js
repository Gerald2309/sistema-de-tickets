import { describe, expect, it } from 'vitest'
import { estadoDelPlazo } from './plazos.js'

const INICIO = '2026-11-02T10:00:00Z'
const LIMITE = '2026-11-02T20:00:00Z'

/**
 * @param {string} hora
 * @param {string | null} [cumplidoEn]
 */
const enLaHora = (hora, cumplidoEn = null) =>
  estadoDelPlazo({ inicio: INICIO, limite: LIMITE, cumplidoEn, ahora: new Date(`2026-11-02T${hora}:00Z`) })

describe('estadoDelPlazo', () => {
  it('a tiempo mientras queda más del 20 % del plazo', () => {
    const plazo = enLaHora('12:00')
    expect(plazo).toMatchObject({ estado: 'a-tiempo', texto: 'Quedan 8 h' })
    expect(plazo.avance).toBeCloseTo(0.2)
  })

  it('cerca de vencer en el último 20 %', () => {
    expect(enLaHora('18:00').estado).toBe('cerca')
    expect(enLaHora('19:30')).toMatchObject({ estado: 'cerca', texto: 'Quedan 30 min' })
  })

  it('incumplido cuando pasó el límite sin cumplirse', () => {
    expect(enLaHora('21:00')).toEqual({ estado: 'incumplido', texto: 'Vencido hace 1 h', avance: 1 })
  })

  it('una vez cumplido deja de contar y dice si fue a tiempo', () => {
    expect(enLaHora('23:00', '2026-11-02T15:00:00Z')).toEqual({
      estado: 'cumplido',
      texto: 'Cumplido a tiempo',
      avance: 1,
    })
    expect(enLaHora('23:00', '2026-11-02T22:00:00Z')).toEqual({
      estado: 'incumplido',
      texto: 'Cumplido 2 h tarde',
      avance: 1,
    })
  })
})
