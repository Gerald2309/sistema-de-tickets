import { describe, expect, it } from 'vitest'
import { aCuerpoDeIncidente, aIncidente, aParametrosDeListado } from './adaptadores.js'

const DTO = {
  id: 605,
  codigo: 'INC-000605',
  titulo: 'Sin internet en el piso 3',
  descripcion: 'No hay conexión desde las 8.',
  categoria: 'red',
  prioridad: 'alta',
  estado: 'en_progreso',
  estados_siguientes: ['resuelto'],
  solucion: null,
  vencido: true,
  fecha_vencimiento: '2026-10-26T22:00:00Z',
  creador: { id: 7, name: 'Camila Ríos' },
  asignado: null,
  cliente: { id: 2, nombre: 'Banco Aliado' },
  created_at: '2026-10-26T13:00:00Z',
  updated_at: '2026-10-26T14:00:00Z',
  campo_nuevo_que_no_se_usa: 'x',
}

describe('adaptadores de incidentes', () => {
  it('traduce el recurso de la API al modelo del cliente, campo a campo', () => {
    const incidente = aIncidente(DTO)

    expect(incidente).toMatchObject({
      id: 605,
      codigo: 'INC-000605',
      estadosSiguientes: ['resuelto'],
      vencido: true,
      fechaVencimiento: '2026-10-26T22:00:00Z',
      creador: { id: 7, nombre: 'Camila Ríos' },
      asignado: null,
      cliente: { id: 2, nombre: 'Banco Aliado' },
      creadoEn: '2026-10-26T13:00:00Z',
    })
    expect(incidente).not.toHaveProperty('campo_nuevo_que_no_se_usa')
  })

  it('convierte las vistas en los parámetros que entiende la API', () => {
    expect(aParametrosDeListado({ vista: 'para-mi' })).toEqual({ asignados_a_mi: 1 })
    expect(aParametrosDeListado({ vista: 'vencidos' })).toEqual({ vencidos: 1 })
    expect(aParametrosDeListado({ vista: 'todos' })).toEqual({})
  })

  it('usa los nombres de la API para filtros, orden y paginación, y omite lo vacío', () => {
    expect(
      aParametrosDeListado({
        q: '  impresora ',
        asignado: 4,
        creador: 7,
        cliente: 2,
        orden: 'prioridad',
        dir: 'desc',
        pagina: 3,
        por: 25,
      }),
    ).toEqual({
      q: 'impresora',
      usuario_asignado_id: 4,
      usuario_creador_id: 7,
      cliente_id: 2,
      ordenar_por: 'prioridad',
      direccion: 'desc',
      page: 3,
      per_page: 25,
    })
    expect(aParametrosDeListado({ q: '   ' })).toEqual({})
  })

  it('las fechas del filtro cubren el día local completo y pueden aplicarse a la creación', () => {
    const parametros = aParametrosDeListado({ desde: '2026-10-01', hasta: '2026-10-31', sobre: 'creacion' })

    expect(new Date(String(parametros.fecha_desde)).getTime()).toBe(new Date(2026, 9, 1, 0, 0, 0).getTime())
    expect(new Date(String(parametros.fecha_hasta)).getTime()).toBe(new Date(2026, 9, 31, 23, 59, 59).getTime())
    expect(parametros.campo_fecha).toBe('creacion')
  })

  it('el cuerpo de escritura lleva solo los campos presentes, con los nombres de la API', () => {
    expect(aCuerpoDeIncidente({ estado: 'resuelto', solucion: 'Se cambió el cable.' })).toEqual({
      estado: 'resuelto',
      solucion: 'Se cambió el cable.',
    })
    expect(aCuerpoDeIncidente({ asignadoId: null, fechaVencimiento: '2026-10-27T08:00:00-05:00' })).toEqual({
      usuario_asignado_id: null,
      fecha_vencimiento: '2026-10-27T08:00:00-05:00',
    })
  })
})
