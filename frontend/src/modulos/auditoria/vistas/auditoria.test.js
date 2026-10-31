import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 1,
  name: 'Ana Gómez',
  email: 'ana.gomez@psicol.co',
  rol: { id: 1, nombre: 'Administrador' },
  cliente: null,
  permisos: ['ver_auditoria'],
}

const REGISTROS = [
  {
    id: 31,
    accion: 'login_fallido',
    usuario: null,
    usuario_afectado: null,
    detalle: 'desconocido@empresa-ejemplo.com',
    ip: '10.0.0.8',
    created_at: '2026-10-26T13:00:00Z',
  },
  {
    id: 30,
    accion: 'rol_asignado',
    usuario: { id: 1, name: 'Ana Gómez' },
    usuario_afectado: { id: 9, name: 'Luis Pardo' },
    detalle: 'Solicitante → Soporte N1',
    ip: '10.0.0.2',
    created_at: '2026-10-26T12:00:00Z',
  },
]

/** Parámetros de cada consulta de auditoría recibida. */
let consultas = []

beforeEach(() => {
  consultas = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('personas'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('auditoria'), ({ request }) => {
      const parametros = Object.fromEntries(new URL(request.url).searchParams)
      consultas.push(parametros)
      const elegidos = parametros.accion ? REGISTROS.filter((r) => r.accion === parametros.accion) : REGISTROS
      return HttpResponse.json({
        data: elegidos,
        meta: { current_page: 1, last_page: 1, per_page: 25, total: elegidos.length },
      })
    }),
  )
})

describe('auditoría', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('cada registro muestra la acción con su nombre, quién, a quién y desde qué IP', async () => {
    montada = await montarAplicacion('/administracion/auditoria')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Rol asignado'), { timeout: 8000 })
    const filas = montada.envoltorio
      .findAll('table tr')
      .filter((fila) => fila.find('td').exists())
      .map((fila) => fila.text())
    expect(filas).toHaveLength(2)
    expect(filas[0]).toContain('Inicio de sesión fallido')
    expect(filas[0]).toContain('10.0.0.8')
    expect(filas[1]).toContain('Luis Pardo')
  })

  it('el filtro de la dirección llega a la API y las fechas cubren el día local', async () => {
    montada = await montarAplicacion('/administracion/auditoria?accion=login_fallido&desde=2026-10-01')

    await vi.waitFor(() => expect(consultas.at(-1)).toMatchObject({ accion: 'login_fallido' }), { timeout: 8000 })
    // Desde el comienzo del día local, con su desfase.
    expect(consultas.at(-1)?.desde).toMatch(/^2026-10-01T00:00:00[+-]\d{2}:\d{2}$/)
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Inicio de sesión fallido'))
    expect(montada.envoltorio.text()).not.toContain('Rol asignado')
  })
})
