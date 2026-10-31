import { describe, expect, it } from 'vitest'
import { describirEntrada, personasDelHistorial } from './historial.js'

/** @returns {import('./tipos.js').EntradaHistorial} */
const entrada = (cambios) => ({
  id: 1,
  evento: 'modificacion',
  campo: null,
  valorAnterior: null,
  valorNuevo: null,
  autor: { id: 7, nombre: 'Camila Ríos' },
  creadoEn: '2026-10-26T13:00:00Z',
  ...cambios,
})
const nombres = (/** @type {number} */ id) => ({ 4: 'Luis Pardo' })[id]

describe('historial legible', () => {
  it('nombra los valores como la interfaz, nunca con los valores internos', () => {
    const descrita = describirEntrada(
      entrada({ campo: 'estado', valorAnterior: 'abierto', valorNuevo: 'en_progreso' }),
      nombres,
    )

    expect(descrita.texto).toBe('Cambió estado')
    expect(descrita.detalle).toBe('Abierto → En progreso')
    expect(descrita.color).toBe('var(--estado-en-progreso)')
  })

  it('describe la creación, el borrado y la asignación con frases propias', () => {
    expect(describirEntrada(entrada({ evento: 'creacion' }), nombres).texto).toBe('Creó el incidente')
    expect(describirEntrada(entrada({ evento: 'borrado' }), nombres).texto).toBe('Eliminó el incidente')
    expect(describirEntrada(entrada({ campo: 'usuario_asignado', valorNuevo: '4' }), nombres).texto).toBe(
      'Asignó a Luis Pardo',
    )
    expect(describirEntrada(entrada({ campo: 'usuario_asignado', valorAnterior: '4' }), nombres).texto).toBe(
      'Quitó la asignación',
    )
  })

  it('sin el nombre de la persona (sin permiso para verlo) no muestra su id', () => {
    expect(describirEntrada(entrada({ campo: 'usuario_asignado', valorNuevo: '99' }), nombres).texto).toBe(
      'Asignó a otra persona',
    )
  })

  it('el vencimiento guardado en UTC se muestra en hora local', () => {
    const descrita = describirEntrada(
      entrada({ campo: 'fecha_vencimiento', valorAnterior: null, valorNuevo: '2026-10-27 13:00:00' }),
      nombres,
    )

    const esperado = new Date('2026-10-27T13:00:00Z')
    expect(descrita.detalle).toContain(String(esperado.getDate()))
    expect(descrita.detalle.startsWith('— → ')).toBe(true)
  })

  it('reúne las personas del historial una sola vez para pedir sus nombres', () => {
    const entradas = [
      entrada({ campo: 'usuario_asignado', valorAnterior: null, valorNuevo: '4' }),
      entrada({ id: 2, campo: 'usuario_asignado', valorAnterior: '4', valorNuevo: '9' }),
      entrada({ id: 3, campo: 'titulo', valorAnterior: 'a', valorNuevo: 'b' }),
    ]

    expect(personasDelHistorial(entradas)).toEqual([4, 9])
  })
})
