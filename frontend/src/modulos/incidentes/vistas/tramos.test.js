import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

// El historial y la conversación llegan por tramos: lo más reciente primero y «ver más» trae lo anterior, sin
// crecer sin fin.

const PERFIL = {
  id: 7,
  name: 'Camila Ríos',
  email: 'camila.rios@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: ['ver_todos', 'cambiar_estado', 'ver_historial'],
}

const INCIDENTE = {
  id: 5,
  codigo: 'INC-000005',
  titulo: 'Incidente 5',
  descripcion: 'Descripción',
  categoria: 'red',
  prioridad: 'alta',
  estado: 'en_progreso',
  estados_siguientes: ['resuelto'],
  solucion: null,
  calificacion: null,
  mensajes_sin_leer: 0,
  vencido: false,
  fecha_vencimiento: '2030-01-01T13:00:00Z',
  creador: { id: 30, name: 'Úrsula Apodaca' },
  asignado: { id: 7, name: 'Camila Ríos' },
  cliente: null,
  created_at: '2026-10-26T13:00:00Z',
  updated_at: '2026-10-26T13:00:00Z',
}

/** @param {number} id */
const cambio = (id) => ({
  id,
  evento: 'modificacion',
  campo: 'titulo',
  valor_anterior: `Antes del cambio ${id}`,
  valor_nuevo: `Título ${id}`,
  autor: { id: 7, name: 'Camila Ríos' },
  created_at: '2026-10-26T14:00:00Z',
})

/** @param {number} id */
const mensaje = (id) => ({
  id,
  cuerpo: `Mensaje ${id}`,
  interno: false,
  autor: { id: 30, name: 'Úrsula Apodaca' },
  adjuntos: [],
  created_at: '2026-10-26T14:00:00Z',
})

/** Cursores con los que se pidió cada lista: null es el primer tramo. */
let cursores = { historial: /** @type {(string | null)[]} */ ([]), mensajes: /** @type {(string | null)[]} */ ([]) }

/**
 * Primer tramo con `primeros` y un cursor; el siguiente, con `anteriores` y sin más.
 * @param {'historial' | 'mensajes'} lista
 * @param {any[]} primeros
 * @param {any[]} anteriores
 */
const porTramos =
  (lista, primeros, anteriores) =>
  ({ request }) => {
    const cursor = new URL(request.url).searchParams.get('cursor')
    cursores[lista].push(cursor)
    return HttpResponse.json(
      cursor ? { data: anteriores, meta: { next_cursor: null } } : { data: primeros, meta: { next_cursor: 'tramo-2' } },
    )
  }

beforeEach(() => {
  cursores = { historial: [], mensajes: [] }
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: INCIDENTE })),
    manejador.get(api('incidentes/:id/similares'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('incidentes/:id/historial'), porTramos('historial', [cambio(12), cambio(11)], [cambio(10)])),
    manejador.get(api('incidentes/:id/mensajes'), porTramos('mensajes', [mensaje(22), mensaje(21)], [mensaje(20)])),
    manejador.put(api('incidentes/:id/lectura'), () => new HttpResponse(null, { status: 204 })),
    manejador.get(api('personas'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('avisos'), () => HttpResponse.json({ data: null })),
    manejador.get(api('catalogos'), () =>
      HttpResponse.json({
        data: { estados: [], prioridades: ['baja', 'media', 'alta', 'critica'], categorias: ['red'] },
      }),
    ),
  )
})

describe('detalle por tramos', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  /** @param {string} texto */
  const pulsar = async (texto) => {
    await montada.envoltorio
      .findAll('button')
      .find((boton) => boton.text().includes(texto))
      ?.trigger('click')
    await esperarPintado()
  }

  it('«ver cambios anteriores» trae el tramo siguiente del historial y desaparece al llegar al inicio', async () => {
    montada = await montarAplicacion('/incidentes/5')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Título 12'), { timeout: 8000 })
    expect(montada.envoltorio.text()).not.toContain('Título 10')

    await pulsar('Ver cambios anteriores')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Título 10'))
    expect(cursores.historial).toEqual([null, 'tramo-2'])
    expect(montada.envoltorio.text()).not.toContain('Ver cambios anteriores')
  })

  it('«ver mensajes anteriores» trae lo más viejo arriba, en orden de lectura', async () => {
    montada = await montarAplicacion('/incidentes/5')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Mensaje 22'), { timeout: 8000 })
    expect(montada.envoltorio.text()).not.toContain('Mensaje 20')

    await pulsar('Ver mensajes anteriores')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Mensaje 20'))
    expect(cursores.mensajes).toEqual([null, 'tramo-2'])
    const enPantalla = montada.envoltorio.findAll('.mensaje__cuerpo').map((parrafo) => parrafo.text())
    expect(enPantalla).toEqual(['Mensaje 20', 'Mensaje 21', 'Mensaje 22'])
    expect(montada.envoltorio.text()).not.toContain('Ver mensajes anteriores')
  })
})
