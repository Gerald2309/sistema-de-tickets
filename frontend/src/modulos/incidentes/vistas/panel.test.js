import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'
import { tiempoReal } from '@/compartido/tiempo-real.js'

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

const DIA = 24 * 3600

const INDICADORES = {
  espera: [
    { hasta_dias: 1, cantidad: 10 },
    { hasta_dias: 3, cantidad: 12 },
    { hasta_dias: 7, cantidad: 20 },
    { hasta_dias: 28, cantidad: 90 },
    { hasta_dias: null, cantidad: 68 },
  ],
  tendencia: Array.from({ length: 14 }, (_, n) => ({
    fecha: `2026-10-${String(18 + n).padStart(2, '0')}`,
    entraron: 5,
    resueltos: n < 4 ? 2 : 5,
  })),
  resolucion: {
    dias: 30,
    mediana_segundos: 2.4 * DIA,
    plazo_segundos: 3 * DIA,
    mediana_anterior_segundos: 3 * DIA,
    a_tiempo: 0.78,
    por_prioridad: {
      critica: { mediana_segundos: 5 * 3600, plazo_segundos: 2 * 3600, a_tiempo: 0.92, cantidad: 12 },
      alta: { mediana_segundos: 1.1 * DIA, plazo_segundos: 2 * DIA, a_tiempo: 0.84, cantidad: 30 },
      media: { mediana_segundos: null, plazo_segundos: null, a_tiempo: null, cantidad: 0 },
      baja: { mediana_segundos: 4.2 * DIA, plazo_segundos: 5 * DIA, a_tiempo: 0.71, cantidad: 20 },
    },
    primera_respuesta_segundos: 3 * 3600,
    respuesta_a_tiempo: 0.85,
    devueltos: 0.06,
  },
}

/** Peticiones de listado y conteo recibidas. */
let listados = []
/** Parámetros con que se pidieron los indicadores. */
let pedidosDeIndicadores = []

beforeEach(() => {
  listados = []
  pedidosDeIndicadores = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('dashboard'), () => HttpResponse.json({ data: RESUMEN })),
    manejador.get(api('dashboard/indicadores'), ({ request }) => {
      pedidosDeIndicadores.push(Object.fromEntries(new URL(request.url).searchParams))
      return HttpResponse.json({ data: INDICADORES })
    }),
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

  it('dice «en vivo» solo mientras el tiempo real está conectado', async () => {
    montada = await montarAplicacion('/panel')
    const subtitulo = () => montada.envoltorio.find('.subtitulo')

    tiempoReal.conectado = true
    await vi.waitFor(() => expect(subtitulo().text()).toContain('en vivo'), { timeout: 8000 })
    expect(subtitulo().text()).not.toContain('sin conexión')
    expect(subtitulo().find('.vivo--conectado').exists()).toBe(true)

    tiempoReal.conectado = false
    await vi.waitFor(() => expect(subtitulo().text()).toContain('sin conexión en vivo'))
    expect(subtitulo().find('.vivo--conectado').exists()).toBe(false)
  })

  it('el recorrido muestra cada estado con lo vencido de las etapas pendientes', async () => {
    montada = await montarAplicacion('/panel')

    await vi.waitFor(() => expect(montada.envoltorio.find('.flujo').exists()).toBe(true), { timeout: 8000 })
    const etapas = montada.envoltorio.findAll('.etapa').map((etapa) => etapa.text())
    expect(etapas[0]).toContain('150 vencidos')
    expect(etapas[2]).toContain('esperan confirmación')
    expect(etapas[3]).toContain('17 % del total')
  })

  it('«Críticos vencidos» pide al servidor solo los críticos fuera de plazo, los más vencidos primero', async () => {
    montada = await montarAplicacion('/panel')

    await vi.waitFor(() =>
      expect(listados).toContainEqual(
        expect.objectContaining({
          prioridad: 'critica',
          vencidos: '1',
          ordenar_por: 'fecha_vencimiento',
          direccion: 'asc',
        }),
      ),
    )
  })

  it('los indicadores se piden con la zona horaria del navegador y se cuentan en palabras', async () => {
    montada = await montarAplicacion('/panel')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('2,4 días'), { timeout: 8000 })
    expect(pedidosDeIndicadores[0]).toEqual({ desfase: String(-new Date().getTimezoneOffset()) })
    const texto = montada.envoltorio.text()
    expect(texto).toContain('0,6 días menos que el periodo anterior')
    expect(texto).toContain('frente a 3 días del SLA')
    expect(texto).toContain('85 % de las primeras respuestas dentro del SLA')
    const [critica, alta] = montada.envoltorio.findAll('.resolucion__tabla tbody tr')
    expect(critica.find('.resolucion__excede').text()).toBe('5 h')
    expect(alta.find('.resolucion__excede').exists()).toBe(false)
    expect(texto).toContain('La mayoría lleva más de una semana')
    expect(texto).toContain('Entran más de los que se resuelven: la cola creció 12.')
    expect(texto).toContain('3 h hasta la primera respuesta')
    expect(texto).toContain('6 % de las soluciones devueltas')
    // Una prioridad sin resueltos no inventa una mediana.
    const media = montada.envoltorio.findAll('.resolucion__tabla tbody tr')[2].text()
    expect(media).toContain('—')
  })
})
