import { describe, expect, it } from 'vitest'
import { consultaDesdeFiltros, filtrosDesdeConsulta } from './filtros.js'

describe('filtros en la dirección', () => {
  it('lee los filtros válidos y descarta en silencio lo desconocido o mal formado', () => {
    const filtros = filtrosDesdeConsulta({
      estado: 'abierto',
      prioridad: 'urgentisima',
      asignado: '12',
      creador: '-3',
      desde: '2026-10-01',
      hasta: '1 de octubre',
      por: '25',
      pagina: '2',
      inventado: 'x',
    })

    expect(filtros).toEqual({ estado: 'abierto', asignado: 12, desde: '2026-10-01', por: 25, pagina: 2 })
  })

  it('solo acepta los tamaños de página que permite la API', () => {
    expect(filtrosDesdeConsulta({ por: '1000' }).por).toBeUndefined()
  })

  it('recorta una búsqueda demasiado larga', () => {
    expect(filtrosDesdeConsulta({ q: 'a'.repeat(300) }).q).toHaveLength(100)
  })

  it('«Para mí» manda sobre «asignado a», y «Vencidos» sobre «vencen en»: la API no los combina', () => {
    expect(filtrosDesdeConsulta({ vista: 'para-mi', asignado: '4' })).toEqual({ vista: 'para-mi' })
    expect(filtrosDesdeConsulta({ vista: 'vencidos', vence: '24' })).toEqual({ vista: 'vencidos' })
  })

  it('la dirección guarda solo lo que se aparta de los valores por defecto', () => {
    expect(
      consultaDesdeFiltros({ vista: 'todos', pagina: 1, por: 15, sobre: 'vencimiento', estado: 'resuelto' }),
    ).toEqual({
      estado: 'resuelto',
    })
  })

  it('ida y vuelta: leer la consulta escrita reproduce los mismos filtros', () => {
    const filtros = {
      vista: 'vencidos',
      q: 'impresora',
      prioridad: 'alta',
      cliente: 3,
      orden: 'prioridad',
      dir: 'desc',
      pagina: 4,
    }

    expect(filtrosDesdeConsulta(consultaDesdeFiltros(/** @type {any} */ (filtros)))).toEqual(filtros)
  })
})
