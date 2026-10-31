import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 7,
  name: 'Camila Ríos',
  email: 'camila.rios@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: ['ver_todos', 'ver_dashboard'],
}

const RESUMEN = {
  total: 605,
  por_estado: { abierto: 120, en_progreso: 80, resuelto: 300, cerrado: 105 },
  por_prioridad: { baja: 200, media: 250, alta: 120, critica: 35 },
  vencidos: 212,
  vencidos_por_estado: { abierto: 150, en_progreso: 62 },
}

/** Peticiones de listado y conteo recibidas. */
let listados = []

beforeEach(() => {
  listados = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('dashboard'), () => HttpResponse.json({ data: RESUMEN })),
    manejador.get(api('avisos'), () =>
      HttpResponse.json({
        data: [
          {
            categoria: 'red',
            incidentes: 5,
            personas: 4,
            palabras: ['internet', 'conexión'],
            desde: new Date(Date.now() - 2 * 3600 * 1000).toISOString(),
          },
        ],
      }),
    ),
    manejador.get(api('incidentes'), ({ request }) => {
      listados.push(Object.fromEntries(new URL(request.url).searchParams))
      return HttpResponse.json({ data: [], meta: { current_page: 1, last_page: 1, per_page: 5, total: 3 } })
    }),
  )
})

describe('panel', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('muestra el flujo por estado y el aviso de incidente masivo', async () => {
    montada = await montarAplicacion('/panel')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Posible incidente masivo en Red'), {
      timeout: 8000,
    })
    const aviso = montada.envoltorio.find('.masivo').text()
    expect(aviso).toContain('5 reportes de 4 personas')
    expect(aviso).toContain('internet')
    expect(montada.envoltorio.find('h1').text()).toBe('Operación de hoy')
  })

  it('«Vencen pronto» pide al servidor solo lo que vence en 24 horas, lo más urgente primero', async () => {
    montada = await montarAplicacion('/panel')

    await vi.waitFor(() =>
      expect(listados).toContainEqual(
        expect.objectContaining({ vence_en_horas: '24', ordenar_por: 'fecha_vencimiento', direccion: 'asc' }),
      ),
    )
  })
})
