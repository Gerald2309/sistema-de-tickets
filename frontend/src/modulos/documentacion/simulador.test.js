import { describe, expect, it } from 'vitest'
import { decidir } from './simulador.js'

/** @type {import('./simulador.js').IncidenteDePrueba} */
const critico = { estado: 'abierto', prioridad: 'critica', respondido: false, ejecutada: false, consumo: 85 }

describe('simulador de «Críticos a punto de vencer»', () => {
  it('un crítico abierto, sin respuesta y al 85 % entra y toma el camino sí con sus dos acciones', () => {
    expect(decidir(critico)).toMatchObject({
      leToca: true,
      camino: 'si',
      acciones: ['Avisar al rol Administrador', 'Nota interna'],
    })
  })

  it('si no es crítico entra igual, pero toma el camino no y no hace nada', () => {
    expect(decidir({ ...critico, prioridad: 'alta' })).toMatchObject({ leToca: true, camino: 'no', acciones: [] })
  })

  it.each([
    ['resuelto', { estado: 'resuelto' }],
    ['ya respondido', { respondido: true }],
    ['ya vencido', { consumo: 100 }],
    ['antes del 80 %', { consumo: 79 }],
    ['ya ejecutado', { ejecutada: true }],
  ])('queda fuera de la revisión si está %s', (_caso, cambio) => {
    expect(decidir({ ...critico, ...cambio })).toMatchObject({ leToca: false, camino: null, acciones: [] })
  })

  it('el 80 % justo ya cuenta, como el >= del SQL', () => {
    expect(decidir({ ...critico, consumo: 80 }).leToca).toBe(true)
  })
})
