import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 4,
  name: 'Diego Betancourt',
  email: 'diego@psicol.co',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: ['ver_todos'],
}

let leidos = 0

beforeEach(() => {
  leidos = 0
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('perfil/avisos'), () =>
      HttpResponse.json({
        data: [
          {
            id: '9b1d',
            texto: '«Red a N2»: INC-000042 · Sin internet',
            incidente_id: 42,
            leido: leidos > 0,
            created_at: new Date().toISOString(),
          },
        ],
        meta: { sin_leer: leidos > 0 ? 0 : 1 },
      }),
    ),
    manejador.post(api('perfil/avisos/leidos'), () => {
      leidos += 1
      return new HttpResponse(null, { status: 204 })
    }),
  )
})

describe('campanita de avisos', { timeout: 20_000 }, () => {
  /** @type {Awaited<ReturnType<typeof montarAplicacion>> | undefined} */
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('dice cuántos avisos faltan por leer y los marca todos como leídos', async () => {
    montada = await montarAplicacion('/sin-permiso')

    await vi.waitFor(() => expect(montada?.envoltorio.find('[aria-label="Avisos: 1 sin leer"]').exists()).toBe(true), {
      timeout: 8000,
    })
    await montada.envoltorio.find('[aria-label="Avisos: 1 sin leer"]').trigger('click')
    await esperarPintado()
    expect(document.body.textContent).toContain('«Red a N2»: INC-000042 · Sin internet')

    const marcar = [...document.querySelectorAll('button')].find((boton) => boton.textContent?.includes('Marcar todos'))
    marcar?.click()

    await vi.waitFor(() => expect(montada?.envoltorio.find('[aria-label="Avisos"]').exists()).toBe(true), {
      timeout: 8000,
    })
    expect(leidos).toBe(1)
  })
})
