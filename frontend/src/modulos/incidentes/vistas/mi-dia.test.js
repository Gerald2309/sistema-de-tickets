import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'
import { diaLocal } from '../mi-dia.js'

// «Mi día» de quien atiende incidentes: la agenda, la bandeja, la pizarra de notas y deshacer.

const PERFIL = {
  id: 7,
  name: 'Cristina Guillen',
  email: 'cristina.guillen@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N1' },
  cliente: null,
  permisos: ['ver_todos', 'cambiar_estado', 'ver_historial'],
}

/**
 * @param {number} id
 * @param {Record<string, unknown>} [cambios]
 */
const incidente = (id, cambios = {}) => ({
  id,
  codigo: `INC-${String(id).padStart(6, '0')}`,
  titulo: `Incidente ${id}`,
  prioridad: 'alta',
  estado: 'en_progreso',
  vencido: false,
  fecha_vencimiento: '2099-01-01T13:00:00Z',
  bloqueo: null,
  ...cambios,
})

/** @type {{ metodo: string, ruta: string, cuerpo?: any }[]} */
let escrituras = []

/** @param {string} metodo @param {string} ruta */
const anotar =
  (metodo, ruta) =>
  async (/** @type {{ request: Request }} */ { request }) => {
    const texto = metodo === 'DELETE' ? '' : await request.text()
    escrituras.push({ metodo, ruta, cuerpo: texto ? JSON.parse(texto) : undefined })
  }

beforeEach(() => {
  escrituras = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('mi-dia/plan'), ({ request }) =>
      HttpResponse.json({
        data: [
          {
            id: 50,
            fecha: new URL(request.url).searchParams.get('desde'),
            inicio: 14 * 60,
            duracion: 60,
            incidente: incidente(733, {
              titulo: 'No hay conexión en la sala de juntas',
              bloqueo: 'Esperando al proveedor',
            }),
            nota: null,
          },
        ],
      }),
    ),
    manejador.get(api('mi-dia/por-planear'), () =>
      HttpResponse.json({
        data: [incidente(712, { titulo: 'La VPN se desconecta', vencido: true })],
        meta: { next_cursor: null },
      }),
    ),
    manejador.get(api('mi-dia/pendientes'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('mi-dia/notas'), () =>
      HttpResponse.json({ data: [{ id: 3, texto: 'Llamar a Pedro de compras', hecha: false, incidente: null }] }),
    ),
    manejador.get(api('mi-dia/ultimo'), () =>
      HttpResponse.json({ data: { incidente: incidente(733), accion: 'mensaje', momento: new Date().toISOString() } }),
    ),
    manejador.post(api('mi-dia/plan'), async (contexto) => {
      await anotar('POST', 'plan')(contexto)
      return HttpResponse.json(
        { data: { id: 51, fecha: '2030-01-01', inicio: 600, duracion: 60, incidente: incidente(712), nota: null } },
        { status: 201 },
      )
    }),
    manejador.delete(api('mi-dia/plan/:id'), async (contexto) => {
      await anotar('DELETE', `plan/${contexto.params.id}`)(contexto)
      return new HttpResponse(null, { status: 204 })
    }),
    manejador.post(api('mi-dia/notas'), async (contexto) => {
      await anotar('POST', 'notas')(contexto)
      return HttpResponse.json(
        { data: { id: 4, texto: 'Enviar mensaje a Ana', hecha: false, incidente: null } },
        { status: 201 },
      )
    }),
  )
})

describe('Mi día', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
  })

  it('reúne la agenda con su marca de bloqueado, la bandeja por urgencia, la pizarra y lo último que tocó', async () => {
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('No hay conexión en la sala de juntas'), {
      timeout: 8000,
    })

    expect(envoltorio.find('.bloque').text()).toContain('14:00–15:00')
    expect(envoltorio.find('.bloque').text()).toContain('Bloqueado: Esperando al proveedor')
    expect(envoltorio.find('.bandeja').text()).toContain('Vencen hoy · 1')
    expect(envoltorio.find('.pizarra').text()).toContain('Llamar a Pedro de compras')
    expect(envoltorio.find('[aria-label="Ahora"]').text()).toContain('Último en el que trabajó')
    expect(envoltorio.findAll('a').some((enlace) => enlace.text().includes('Mi día'))).toBe(true)
  })

  it('una nota se escribe y se guarda con Enter', async () => {
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('.pizarra textarea').exists()).toBe(true), { timeout: 8000 })

    const campo = envoltorio.find('.pizarra textarea')
    await campo.setValue('Enviar mensaje a Ana')
    await campo.trigger('keydown', { key: 'Enter' })

    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({ metodo: 'POST', ruta: 'notas', cuerpo: { texto: 'Enviar mensaje a Ana' } }),
    )
  })

  it('«Planear ▾» pone el incidente hoy sin arrastrar', async () => {
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('.bandeja').text()).toContain('La VPN se desconecta'), {
      timeout: 8000,
    })

    await envoltorio
      .find('.bandeja')
      .findAll('button')
      .find((boton) => boton.text().includes('Planear'))
      ?.trigger('click')
    await esperarPintado()
    const hoy = [...document.querySelectorAll('.v-list-item')].find((item) =>
      item.textContent?.includes('Hoy, en el próximo hueco'),
    )
    if (hoy instanceof HTMLElement) {
      hoy.click()
    }

    await vi.waitFor(() =>
      expect(escrituras.find((escritura) => escritura.ruta === 'plan')?.cuerpo).toMatchObject({
        incidente_id: 712,
        fecha: diaLocal(new Date()),
      }),
    )
  })

  it('quitar de la agenda se deshace desde el aviso, en el mismo lugar', async () => {
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('.bloque').exists()).toBe(true), { timeout: 8000 })

    await envoltorio.find('.bloque [aria-label="Más opciones"]').trigger('click')
    await esperarPintado()
    const quitar = [...document.querySelectorAll('.v-list-item')].find((item) =>
      item.textContent?.includes('Quitar de hoy'),
    )
    if (quitar instanceof HTMLElement) {
      quitar.click()
    }
    await vi.waitFor(() => expect(escrituras).toContainEqual({ metodo: 'DELETE', ruta: 'plan/50', cuerpo: undefined }))

    await vi.waitFor(() => expect(document.body.textContent).toContain('INC-000733 volvió a la bandeja'))
    const deshacer = [...document.querySelectorAll('.aviso__accion')].find((boton) =>
      boton.textContent?.includes('Deshacer'),
    )
    if (deshacer instanceof HTMLElement) {
      deshacer.click()
    }
    await vi.waitFor(() =>
      expect(
        escrituras.find((escritura) => escritura.metodo === 'POST' && escritura.ruta === 'plan')?.cuerpo,
      ).toMatchObject({
        incidente_id: 733,
        inicio: 14 * 60,
        duracion: 60,
      }),
    )
  })

  it('«Sugerir mi día» agrega lo urgente desde la hora actual y se deshace quitando lo agregado', async () => {
    servidor.use(
      manejador.post(api('mi-dia/sugerencia'), async (contexto) => {
        await anotar('POST', 'sugerencia')(contexto)
        return HttpResponse.json({
          data: [61, 62].map((id) => ({
            id,
            fecha: '2030-01-01',
            inicio: 600,
            duracion: 60,
            incidente: incidente(id),
            nota: null,
          })),
        })
      }),
    )
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('.bloque').exists()).toBe(true), { timeout: 8000 })

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text().includes('Sugerir mi día'))
      ?.trigger('click')

    await vi.waitFor(() =>
      expect(escrituras.find((escritura) => escritura.ruta === 'sugerencia')?.cuerpo).toMatchObject({
        fecha: diaLocal(new Date()),
      }),
    )
    await vi.waitFor(() => expect(document.body.textContent).toContain('Se agregaron 2 al día'))
    const deshacer = [...document.querySelectorAll('.aviso__accion')].find((boton) =>
      boton.textContent?.includes('Deshacer'),
    )
    if (deshacer instanceof HTMLElement) {
      deshacer.click()
    }
    await vi.waitFor(() =>
      expect(
        escrituras.filter((escritura) => escritura.metodo === 'DELETE').map((escritura) => escritura.ruta),
      ).toEqual(['plan/61', 'plan/62']),
    )
  })

  it('el menú del bloque quita la marca personal de bloqueado', async () => {
    servidor.use(
      manejador.delete(api('mi-dia/bloqueos/:id'), async (contexto) => {
        await anotar('DELETE', `bloqueos/${contexto.params.id}`)(contexto)
        return new HttpResponse(null, { status: 204 })
      }),
    )
    montada = await montarAplicacion('/mi-dia')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('.bloque').exists()).toBe(true), { timeout: 8000 })

    // El bloque de la agenda ya viene bloqueado: su menú ofrece quitar la marca.
    await envoltorio.find('.bloque [aria-label="Más opciones"]').trigger('click')
    await esperarPintado()
    const quitar = [...document.querySelectorAll('.v-list-item')].find((item) =>
      item.textContent?.includes('Quitar el bloqueo'),
    )
    if (quitar instanceof HTMLElement) {
      quitar.click()
    }

    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({ metodo: 'DELETE', ruta: 'bloqueos/733', cuerpo: undefined }),
    )
  })
})
