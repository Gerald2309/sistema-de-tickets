import { describe, expect, it } from 'vitest'
import {
  agruparPorUrgencia,
  ahoraYSiguiente,
  cuadriculaDelMes,
  duracionLegible,
  hora,
  esFinDeSemana,
  semanaDe,
  siguienteDuracion,
  sumarDias,
} from './mi-dia.js'

/**
 * @param {number} id
 * @param {number} inicio
 * @param {Partial<import('./api/mi-dia.js').EntradaDelPlan>} [cambios]
 * @returns {import('./api/mi-dia.js').EntradaDelPlan}
 */
const entrada = (id, inicio, cambios = {}) => ({
  id,
  fecha: '2030-01-15',
  inicio,
  duracion: 60,
  incidente: {
    id,
    codigo: `INC-${id}`,
    titulo: 'x',
    prioridad: 'media',
    estado: 'en_progreso',
    vencido: false,
    fechaVencimiento: '2030-01-20T00:00:00Z',
    bloqueo: null,
  },
  nota: null,
  ...cambios,
})

describe('Mi día', () => {
  it('cuenta los días locales: semana de lunes a domingo, mes en 6 semanas y saltos de mes', () => {
    expect(semanaDe('2030-01-17')).toEqual([
      '2030-01-14',
      '2030-01-15',
      '2030-01-16',
      '2030-01-17',
      '2030-01-18',
      '2030-01-19',
      '2030-01-20',
    ])
    expect(esFinDeSemana('2030-01-19')).toBe(true)
    expect(esFinDeSemana('2030-01-18')).toBe(false)
    expect(semanaDe('2030-01-20')[0]).toBe('2030-01-14')
    const mes = cuadriculaDelMes('2030-01-15')
    expect(mes).toHaveLength(42)
    expect([mes[0], mes[1]]).toEqual(['2029-12-31', '2030-01-01'])
    expect(sumarDias('2030-01-31', 1)).toBe('2030-02-01')
  })

  it('escribe horas y duraciones como se leen', () => {
    expect([hora(600), hora(630), hora(1080)]).toEqual(['10:00', '10:30', '18:00'])
    expect([duracionLegible(30), duracionLegible(60), duracionLegible(90)]).toEqual(['30 min', '1 h', '1,5 h'])
    expect([siguienteDuracion(30), siguienteDuracion(180)]).toEqual([60, 30])
  })

  it('«ahora» es lo trabajable en curso y «siguiente» lo que viene, sin lo bloqueado ni lo terminado', () => {
    const hecha = entrada(1, 480, { incidente: { ...entrada(1, 0).incidente, estado: 'resuelto' } })
    const enCurso = entrada(2, 600, { duracion: 90 })
    const bloqueada = entrada(3, 720, { incidente: { ...entrada(3, 0).incidente, bloqueo: 'proveedor' } })
    const despues = entrada(4, 840)

    const { ahora, siguiente } = ahoraYSiguiente([hecha, enCurso, bloqueada, despues], 620)
    expect(ahora?.id).toBe(2)
    expect(siguiente?.id).toBe(4)
    expect(ahoraYSiguiente([hecha], 620)).toEqual({ ahora: null, siguiente: null })
  })

  it('agrupa lo por planear en vencen hoy, esta semana y más adelante', () => {
    const ahora = new Date(2030, 0, 15, 10, 0)
    const enDias = (/** @type {number} */ dias) => new Date(2030, 0, 15 + dias, 9, 0).toISOString()
    const grupos = agruparPorUrgencia(
      [
        { id: 1, vencido: true, fechaVencimiento: enDias(-1) },
        { id: 2, vencido: false, fechaVencimiento: enDias(0) },
        { id: 3, vencido: false, fechaVencimiento: enDias(3) },
        { id: 4, vencido: false, fechaVencimiento: enDias(10) },
      ],
      ahora,
    )
    expect(grupos.map((grupo) => [grupo.clave, grupo.elementos.map((incidente) => incidente.id)])).toEqual([
      ['hoy', [1, 2]],
      ['semana', [3]],
      ['despues', [4]],
    ])
  })
})
