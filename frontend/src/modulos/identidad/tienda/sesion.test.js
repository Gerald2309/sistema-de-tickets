import { http as manejador, HttpResponse } from 'msw'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api, servidor } from '../../../../pruebas/servidor.js'
import { conectarSesion } from '@/compartido/api/cliente.js'
import { useSesion } from './sesion.js'

const PERFIL = {
  id: 7,
  name: 'Camila Ríos',
  email: 'camila.rios@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: ['ver_dashboard', 'ver_todos'],
}

/** Responde al inicio de sesión y al perfil como el backend. */
function apiDeSesion({ expiraEn = '2099-01-01T00:00:00Z' } = {}) {
  servidor.use(
    manejador.post(api('auth/login'), () =>
      HttpResponse.json({ data: { token: '1|abc', tipo_token: 'Bearer', expira_en: expiraEn } }),
    ),
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.post(api('auth/logout'), () => new HttpResponse(null, { status: 204 })),
  )
}

describe('sesión', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    const sesion = useSesion()
    conectarSesion({ obtenerToken: () => sesion.token, alExpirar: () => sesion.expirar() })
  })
  afterEach(() => vi.useRealTimers())

  it('al iniciar guarda solo el token y la expiración, y lee el perfil de la API', async () => {
    apiDeSesion()
    const sesion = useSesion()

    await sesion.iniciar('camila.rios@empresa-ejemplo.com', 'una contraseña larga')

    expect(sesion.autenticada).toBe(true)
    expect(sesion.perfil?.nombre).toBe('Camila Ríos')
    expect(sesion.tiene('ver_todos')).toBe(true)
    expect(sesion.tieneAlguno('ver_auditoria', 'ver_dashboard')).toBe(true)
    expect(JSON.parse(sessionStorage.getItem('mesa-de-ayuda.sesion') ?? '')).toEqual({
      token: '1|abc',
      expiraEn: '2099-01-01T00:00:00Z',
    })
  })

  it('restaura la sesión guardada al recargar y la limpia si el token ya no sirve', async () => {
    sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
    apiDeSesion()
    setActivePinia(createPinia())
    expect(await useSesion().restaurar()).toBe(true)

    servidor.use(manejador.get(api('me'), () => HttpResponse.json({ detail: 'Falta la credencial.' }, { status: 401 })))
    setActivePinia(createPinia())
    const sesion = useSesion()
    expect(await sesion.restaurar()).toBe(false)
    expect(sesion.token).toBeNull()
    expect(sessionStorage.getItem('mesa-de-ayuda.sesion')).toBeNull()
  })

  it('al cerrar borra la sesión local aunque el servidor no responda', async () => {
    apiDeSesion()
    const sesion = useSesion()
    await sesion.iniciar('camila.rios@empresa-ejemplo.com', 'una contraseña larga')
    servidor.use(manejador.post(api('auth/logout'), () => HttpResponse.error()))

    await sesion.cerrar()

    expect(sesion.autenticada).toBe(false)
    expect(sesion.motivoDeSalida).toBe('cerrada')
    expect(sessionStorage.getItem('mesa-de-ayuda.sesion')).toBeNull()
  })

  it('expira sola a la hora indicada por el servidor', async () => {
    vi.useFakeTimers({ toFake: ['setTimeout', 'clearTimeout', 'Date'] })
    vi.setSystemTime(new Date('2030-01-01T08:00:00Z'))
    apiDeSesion({ expiraEn: '2030-01-01T16:00:00Z' })
    const sesion = useSesion()
    await sesion.iniciar('camila.rios@empresa-ejemplo.com', 'una contraseña larga')

    vi.advanceTimersByTime(8 * 60 * 60 * 1000)

    expect(sesion.token).toBeNull()
    expect(sesion.motivoDeSalida).toBe('expirada')
  })

  it('si falla la lectura del perfil tras iniciar, no queda una sesión a medias', async () => {
    apiDeSesion()
    servidor.use(manejador.get(api('me'), () => HttpResponse.json({ detail: 'Error.' }, { status: 500 })))
    const sesion = useSesion()

    await expect(sesion.iniciar('camila.rios@empresa-ejemplo.com', 'una contraseña larga')).rejects.toThrow()

    expect(sesion.token).toBeNull()
  })
})
