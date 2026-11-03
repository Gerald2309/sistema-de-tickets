import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 1,
  name: 'Ana Gómez',
  email: 'ana.gomez@psicol.co',
  rol: { id: 1, nombre: 'Administrador' },
  cliente: null,
  permisos: ['administrar_automatizaciones'],
}

const GUARDADA = {
  id: 5,
  nombre: 'Seguimiento sin respuesta',
  activa: true,
  disparador: 'sin_respuesta',
  espera_minutos: 240,
  porcentaje: null,
  plazo: null,
  pasos: [{ tipo: 'accion', accion: 'nota_interna', texto: 'Revisar' }],
  autor: { id: 1, name: 'Ana Gómez' },
  aplicaciones: 14,
  aplicaciones_con_error: 2,
  ultima_aplicacion_en: null,
  updated_at: '2026-10-26T12:00:00Z',
}

/** @type {Array<{ metodo: string, ruta: string, cuerpo: any }>} */
let pedidos = []

beforeEach(() => {
  pedidos = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('personas'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('roles'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('clientes'), () =>
      HttpResponse.json({ data: [], meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 } }),
    ),
    manejador.get(api('automatizaciones'), () => HttpResponse.json({ data: [GUARDADA] })),
    manejador.get(api('automatizaciones/5'), () => HttpResponse.json({ data: GUARDADA })),
    manejador.patch(api('automatizaciones/5/estado'), async ({ request }) => {
      const cuerpo = await request.json()
      pedidos.push({ metodo: 'PATCH', ruta: 'estado', cuerpo })
      return HttpResponse.json({ data: { ...GUARDADA, activa: false } })
    }),
  )
})

describe('automatizaciones', { timeout: 20_000 }, () => {
  /** @type {Awaited<ReturnType<typeof montarAplicacion>> | undefined} */
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('la lista dice cuándo se dispara cada una, cuánto se ha usado y permite apagarla', async () => {
    montada = await montarAplicacion('/administracion/automatizaciones')

    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('Seguimiento sin respuesta'), { timeout: 8000 })
    const texto = montada.envoltorio.text()
    expect(texto).toContain('Lleva un tiempo sin respuesta')
    expect(texto).toContain('Sin respuesta de soporte por 4 horas')
    expect(texto).toContain('14 veces')
    expect(texto).toContain('2 se detuvieron por un error')

    await montada.envoltorio.find('input[role="switch"]').setValue(false)
    await vi.waitFor(() => expect(pedidos).toEqual([{ metodo: 'PATCH', ruta: 'estado', cuerpo: { activa: false } }]))
  })

  it('una nueva se arma con bloques y se guarda completa', async () => {
    servidor.use(
      manejador.post(api('automatizaciones'), async ({ request }) => {
        const cuerpo = await request.json()
        pedidos.push({ metodo: 'POST', ruta: 'automatizaciones', cuerpo })
        return HttpResponse.json({ data: { ...GUARDADA, ...cuerpo, id: 5 } }, { status: 201 })
      }),
    )
    montada = await montarAplicacion('/administracion/automatizaciones/nueva')
    await vi.waitFor(() => expect(montada?.envoltorio.find('[aria-label="Añadir un paso aquí"]').exists()).toBe(true), {
      timeout: 8000,
    })

    await montada.envoltorio.find('[aria-label="Añadir un paso aquí"]').trigger('click')
    await esperarPintado()
    const bloque = montada.envoltorio.findAll('button.bloque').find((boton) => boton.text().includes('Nota interna'))
    await bloque?.trigger('click')
    await esperarPintado()
    await montada.envoltorio.find('textarea').setValue('Revisar primero la VPN')
    await esperarPintado()
    const guardar = montada.envoltorio.findAll('button').find((boton) => boton.text() === 'Guardar cambios')
    await guardar?.trigger('click')

    await vi.waitFor(
      () => expect(montada?.router.currentRoute.value.fullPath).toBe('/administracion/automatizaciones/5'),
      {
        timeout: 8000,
      },
    )
    expect(pedidos[0].cuerpo).toEqual({
      nombre: 'Nueva automatización',
      activa: false,
      disparador: 'creacion',
      espera_minutos: null,
      porcentaje: null,
      plazo: null,
      pasos: [{ tipo: 'accion', accion: 'nota_interna', texto: 'Revisar primero la VPN' }],
    })
  })

  it('un error de la API queda marcado en su bloque', async () => {
    servidor.use(
      manejador.put(api('automatizaciones/5'), () =>
        HttpResponse.json(
          {
            type: 'urn:incidentes:problema:validacion',
            title: 'Datos inválidos',
            status: 422,
            detail: 'Revise los datos.',
            errors: { 'pasos.0.texto': ['Escriba el texto.'] },
          },
          { status: 422, headers: { 'Content-Type': 'application/problem+json' } },
        ),
      ),
    )
    montada = await montarAplicacion('/administracion/automatizaciones/5')
    // El «Cuándo» y la nota interna.
    await vi.waitFor(() => expect(montada?.envoltorio.findAll('.paso').length).toBe(2), { timeout: 8000 })

    await montada.envoltorio.find('.editor__nombre').setValue('Otro nombre')
    const guardar = montada.envoltorio.findAll('button').find((boton) => boton.text() === 'Guardar cambios')
    await guardar?.trigger('click')

    await vi.waitFor(() => expect(montada?.envoltorio.find('.paso--error').exists()).toBe(true), { timeout: 8000 })
    expect(montada.envoltorio.find('.paso--error').text()).toContain('Nota interna')
  })
})
