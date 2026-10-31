import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

// Lo que ve quien reporta incidentes: sus solicitudes como inicio, el avance de cada una y la confirmación
// de la solución.

const PERFIL = {
  id: 30,
  name: 'Úrsula Apodaca',
  email: 'ursula.apodaca@empresa-ejemplo.com',
  rol: { id: 2, nombre: 'Solicitante' },
  cliente: null,
  permisos: ['crear_incidente', 'ver_propios', 'editar_propios', 'ver_historial'],
}

/**
 * @param {number} id
 * @param {Record<string, unknown>} [cambios]
 */
const incidente = (id, cambios = {}) => ({
  id,
  codigo: `INC-${String(id).padStart(6, '0')}`,
  titulo: `Solicitud ${id}`,
  descripcion: 'Descripción',
  categoria: 'red',
  prioridad: 'media',
  estado: 'en_progreso',
  estados_siguientes: [],
  solucion: null,
  calificacion: null,
  mensajes_sin_leer: 0,
  vencido: false,
  fecha_vencimiento: '2030-01-01T13:00:00Z',
  creador: { id: 30, name: 'Úrsula Apodaca' },
  asignado: { id: 7, name: 'Cristina Guillen' },
  cliente: null,
  created_at: '2026-10-26T13:00:00Z',
  updated_at: '2026-10-26T13:00:00Z',
  ...cambios,
})

const POR_ESTADO = {
  abierto: [incidente(1, { estado: 'abierto', asignado: null })],
  en_progreso: [incidente(2, { mensajes_sin_leer: 2 })],
  resuelto: [incidente(3, { estado: 'resuelto', solucion: 'Se cambió el cable.' })],
}

/** @type {{ metodo: string, ruta: string, cuerpo?: unknown }[]} */
let escrituras = []

beforeEach(() => {
  escrituras = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('incidentes'), ({ request }) => {
      const estado = /** @type {keyof typeof POR_ESTADO} */ (new URL(request.url).searchParams.get('estado'))
      const elementos = POR_ESTADO[estado] ?? []
      return HttpResponse.json({
        data: elementos,
        meta: { current_page: 1, last_page: 1, per_page: 50, total: elementos.length },
      })
    }),
    manejador.get(api('incidentes/:id'), ({ params }) =>
      HttpResponse.json({
        data: Object.values(POR_ESTADO)
          .flat()
          .find((dto) => dto.id === Number(params.id)),
      }),
    ),
    manejador.get(api('incidentes/:id/historial'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('incidentes/:id/mensajes'), () =>
      HttpResponse.json({
        data: [
          {
            id: 1,
            cuerpo: 'Hola',
            interno: false,
            autor: { id: 7, name: 'Cristina Guillen' },
            adjuntos: [],
            created_at: '2026-10-26T14:00:00Z',
          },
        ],
      }),
    ),
    manejador.put(api('incidentes/:id/lectura'), ({ params }) => {
      escrituras.push({ metodo: 'PUT', ruta: `lectura/${params.id}` })
      return new HttpResponse(null, { status: 204 })
    }),
    manejador.post(api('incidentes/:id/confirmacion'), async ({ request, params }) => {
      const cuerpo = await request.json()
      escrituras.push({ metodo: 'POST', ruta: `confirmacion/${params.id}`, cuerpo })
      return HttpResponse.json({ data: incidente(Number(params.id), { estado: 'en_progreso' }) })
    }),
  )
})

describe('quien reporta incidentes', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
  })

  it('entra a sus solicitudes: primero lo que debe confirmar y debajo lo que sigue en curso', async () => {
    montada = await montarAplicacion('/')
    const { envoltorio, router } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('Se cambió el cable.'), { timeout: 8000 })

    expect(router.currentRoute.value.name).toBe('mis-solicitudes')
    expect(envoltorio.find('#titulo-carrusel').text()).toContain('Confirme si quedó solucionado')
    expect(envoltorio.find('.carrusel').text()).toContain('1 de 1')
    // En curso, la que tiene respuestas sin leer va primero.
    const filas = envoltorio.findAll('.fila-solicitud').map((fila) => fila.text())
    expect(filas).toHaveLength(2)
    expect(filas[0]).toContain('Solicitud 2')
    expect(filas[0]).toContain('2 mensajes nuevos')
    expect(filas[1]).toContain('Esperando a soporte')
    expect(envoltorio.text()).not.toContain('Panel')
  })

  it('en la tarjeta, una estrella cierra la solicitud con esa calificación en un solo paso', async () => {
    montada = await montarAplicacion('/mis-solicitudes')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('Se cambió el cable.'), { timeout: 8000 })

    await envoltorio.find('.por-confirmar [aria-label="Cerrar y calificar 4 de 5"]').trigger('click')

    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({
        metodo: 'POST',
        ruta: 'confirmacion/3',
        cuerpo: { solucionado: true, calificacion: 4 },
      }),
    )
  })

  it('en la tarjeta, «No» pide el motivo ahí mismo y no envía sin él', async () => {
    montada = await montarAplicacion('/mis-solicitudes')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('Se cambió el cable.'), { timeout: 8000 })
    const tarjeta = envoltorio.find('.por-confirmar')

    await tarjeta
      .findAll('button')
      .find((boton) => boton.text() === 'No, sigue pasando')
      ?.trigger('click')
    await esperarPintado()
    await tarjeta.find('form').trigger('submit')
    await esperarPintado()
    expect(tarjeta.text()).toContain('Cuéntenos qué sigue pasando')
    expect(escrituras.some((escritura) => escritura.metodo === 'POST')).toBe(false)

    await tarjeta.find('textarea').setValue('Se volvió a caer.')
    await tarjeta.find('form').trigger('submit')
    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({
        metodo: 'POST',
        ruta: 'confirmacion/3',
        cuerpo: { solucionado: false, motivo: 'Se volvió a caer.' },
      }),
    )
  })

  it('ver la conversación con mensajes nuevos la deja leída', async () => {
    montada = await montarAplicacion('/incidentes/2')
    await vi.waitFor(() => expect(escrituras).toContainEqual({ metodo: 'PUT', ruta: 'lectura/2' }), { timeout: 8000 })
    expect(montada.envoltorio.find('[aria-labelledby="titulo-progreso"]').text()).toContain(
      'La atiende Cristina Guillen',
    )
  })

  it('si no quedó solucionado, exige el motivo y lo envía', async () => {
    montada = await montarAplicacion('/incidentes/3')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('¿Quedó solucionado?'), { timeout: 8000 })
    const confirmar = envoltorio.find('[aria-labelledby="titulo-confirmar"]')

    await confirmar
      .findAll('button')
      .find((boton) => boton.text() === 'No, sigue pasando')
      ?.trigger('click')
    await esperarPintado()
    await confirmar.find('form').trigger('submit')
    await esperarPintado()
    expect(confirmar.text()).toContain('Cuéntenos qué sigue pasando')
    expect(escrituras.some((escritura) => escritura.metodo === 'POST')).toBe(false)

    await confirmar.find('textarea').setValue('  Sigue sin conexión.  ')
    await confirmar.find('form').trigger('submit')
    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({
        metodo: 'POST',
        ruta: 'confirmacion/3',
        cuerpo: { solucionado: false, motivo: 'Sigue sin conexión.' },
      }),
    )
  })

  it('en el detalle, «Sí, quedó» lo cierra sin calificar en un clic', async () => {
    montada = await montarAplicacion('/incidentes/3')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.text()).toContain('¿Quedó solucionado?'), { timeout: 8000 })

    await envoltorio
      .find('[aria-labelledby="titulo-confirmar"]')
      .findAll('button')
      .find((boton) => boton.text().includes('Sí, quedó'))
      ?.trigger('click')

    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({
        metodo: 'POST',
        ruta: 'confirmacion/3',
        cuerpo: { solucionado: true, calificacion: null },
      }),
    )
  })

  it('mientras nadie lo ha tomado, corrige el título y la descripción de lo que reportó', async () => {
    servidor.use(
      manejador.patch(api('incidentes/:id'), async ({ request, params }) => {
        const cuerpo = await request.json()
        escrituras.push({ metodo: 'PATCH', ruta: `incidentes/${params.id}`, cuerpo })
        return HttpResponse.json({
          data: incidente(Number(params.id), { estado: 'abierto', asignado: null, ...cuerpo }),
        })
      }),
    )
    montada = await montarAplicacion('/incidentes/1')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('h1').text()).toBe('Solicitud 1'), { timeout: 8000 })

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text() === 'Corregir')
      ?.trigger('click')
    await vi.waitFor(() => expect(document.querySelector('.dialogo-relato')).not.toBeNull())
    const titulo = /** @type {HTMLInputElement} */ (document.querySelector('.dialogo-relato input'))
    titulo.value = 'No imprime en el piso 2'
    titulo.dispatchEvent(new Event('input'))
    await esperarPintado()
    const formulario = /** @type {HTMLFormElement} */ (document.querySelector('.dialogo-relato form'))
    formulario.requestSubmit()

    await vi.waitFor(() =>
      expect(escrituras).toContainEqual({
        metodo: 'PATCH',
        ruta: 'incidentes/1',
        cuerpo: { titulo: 'No imprime en el piso 2' },
      }),
    )
  })

  it('debajo del carrusel, un resumen por estado y cada solicitud con su camino', async () => {
    montada = await montarAplicacion('/')
    const { envoltorio } = montada
    await vi.waitFor(() => expect(envoltorio.find('[aria-labelledby="titulo-resumen"]').exists()).toBe(true), {
      timeout: 8000,
    })

    const cifras = envoltorio.findAll('.resumen__cifra').map((cifra) => cifra.text())
    expect(cifras).toEqual(['En curso2', 'Le respondieron1', 'Por confirmar1'])
    expect(envoltorio.find('.resumen__leyenda').text()).toContain('Recibidas · 1')

    const caminos = envoltorio.findAll('.fila-solicitud .mini-ruta').map((ruta) => ruta.attributes('aria-label'))
    expect(caminos).toEqual(['Paso 2 de 4: En atención', 'Paso 1 de 4: Esperando a soporte'])
  })
})
