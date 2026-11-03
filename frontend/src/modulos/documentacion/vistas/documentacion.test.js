import { afterEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'

describe('documentación técnica', { timeout: 20_000 }, () => {
  /** @type {Awaited<ReturnType<typeof montarAplicacion>> | undefined} */
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    document.body.innerHTML = ''
  })

  it('se abre sin sesión y sin pedirla, en la página de inicio', async () => {
    montada = await montarAplicacion('/documentacion')

    await vi.waitFor(() => expect(montada?.router.currentRoute.value.params.pagina).toBe('inicio'))
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('Mapa del sistema'))
    expect(montada.router.currentRoute.value.name).toBe('documentacion')
    expect(montada.envoltorio.text()).toContain('Mesa de ayuda · Documentación técnica')
    expect(montada.envoltorio.find('a[href$="backend/routes/api.php"]').exists()).toBe(true)
  })

  it('el mapa del sistema lleva a la página de cada pieza', async () => {
    montada = await montarAplicacion('/documentacion')
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('Mapa del sistema'))

    await montada.envoltorio.find('[role="link"][aria-label^="Seguridad"]').trigger('click')
    await vi.waitFor(() => expect(montada?.router.currentRoute.value.params.pagina).toBe('seguridad'))
  })

  it('cambia el nivel del plano C4', async () => {
    montada = await montarAplicacion('/documentacion/arquitectura')
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('Planos C4'))

    const contenedores = montada.envoltorio.findAll('[role="tab"]').find((boton) => boton.text() === '2 · Contenedores')
    await contenedores?.trigger('click')

    expect(montada.envoltorio.find('[aria-label="Plano A-02: Contenedores"]').exists()).toBe(true)
    expect(montada.envoltorio.text()).toContain('cada caja es un contenedor de Docker Compose')
  })

  it('el recorrido avanza paso a paso y abre el paso que pide la dirección', async () => {
    montada = await montarAplicacion('/documentacion/recorrido?paso=5#seccion-0')
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('6 · Plazos y guardado en una transacción'))

    const siguiente = montada.envoltorio.findAll('button').find((boton) => boton.text() === 'Siguiente')
    await siguiente?.trigger('click')
    expect(montada.envoltorio.text()).toContain('7 · Historial')
  })

  it('el simulador cambia de camino según la prioridad', async () => {
    montada = await montarAplicacion('/documentacion/automatizaciones')
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('toma el camino «sí»'))

    const alta = montada.envoltorio.findAll('[role="tab"]').find((boton) => boton.text() === 'Alta')
    await alta?.trigger('click')
    expect(montada.envoltorio.text()).toContain('toma el camino «no»')
  })

  it('el buscador lleva a la fila exacta de otra página', async () => {
    montada = await montarAplicacion('/documentacion/arquitectura')
    await vi.waitFor(() => expect(montada?.envoltorio.text()).toContain('Planos C4'))

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }))
    await esperarPintado()
    const campo = /** @type {HTMLInputElement} */ (
      document.querySelector(
        '[aria-label="Buscar en la documentación"] input, input[aria-label="Buscar en la documentación"]',
      )
    )
    campo.value = 'insertOrIgnore'
    campo.dispatchEvent(new Event('input'))
    await esperarPintado()
    campo.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }))

    await vi.waitFor(() => expect(montada?.router.currentRoute.value.params.pagina).toBe('automatizaciones'))
    expect(montada.router.currentRoute.value.hash).toMatch(/^#seccion-\d+$/)
  })
})
