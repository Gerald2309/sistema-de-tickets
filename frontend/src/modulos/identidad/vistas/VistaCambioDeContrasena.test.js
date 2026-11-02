import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 7,
  name: 'Marta Ruiz',
  email: 'marta.ruiz@empresa-ejemplo.com',
  rol: { id: 4, nombre: 'Solicitante' },
  cliente: null,
  permisos: ['crear_incidente', 'ver_propios'],
  inicio_anterior: null,
}

let debeCambiarla = true

beforeEach(() => {
  debeCambiarla = true
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: { ...PERFIL, debe_cambiar_contrasena: debeCambiarla } })),
    manejador.get(api('perfil/sesiones'), () => HttpResponse.json({ data: [] })),
    manejador.put(api('perfil/contrasena'), () => {
      debeCambiarla = false
      return new HttpResponse(null, { status: 204 })
    }),
  )
})

describe('cambio obligatorio de la contraseña', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('con una contraseña puesta por un administrador, solo abre el cambio y al terminar sigue a donde iba', async () => {
    montada = await montarAplicacion('/mi-cuenta')
    await vi.waitFor(() => expect(montada.router.currentRoute.value.name).toBe('cambiar-contrasena'), { timeout: 8000 })
    expect(montada.router.currentRoute.value.query.destino).toBe('/mi-cuenta')

    await vi.waitFor(() => expect(montada.envoltorio.findAll('input[type="password"]')).toHaveLength(3), {
      timeout: 8000,
    })
    const [actual, nueva, repetida] = montada.envoltorio.findAll('input[type="password"]')
    await actual.setValue('Una contraseña inicial 2026')
    await nueva.setValue('Mi propia contraseña 2026!')
    await repetida.setValue('Mi propia contraseña 2026!')
    await esperarPintado()
    await montada.envoltorio.find('form').trigger('submit')

    await vi.waitFor(() => expect(montada.router.currentRoute.value.name).toBe('mi-cuenta'))
  })
})
