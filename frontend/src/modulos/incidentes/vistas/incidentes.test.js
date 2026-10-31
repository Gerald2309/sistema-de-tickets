import { http as manejador, HttpResponse } from 'msw'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 7,
  name: 'Camila Ríos',
  email: 'camila.rios@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: ['ver_todos', 'editar_todos', 'cambiar_estado', 'reasignar', 'crear_incidente', 'ver_historial'],
}

/** @param {number} id */
const incidente = (id) => ({
  id,
  codigo: `INC-${String(id).padStart(6, '0')}`,
  titulo: `Incidente ${id}`,
  descripcion: 'Descripción',
  categoria: 'red',
  prioridad: 'alta',
  estado: 'abierto',
  estados_siguientes: ['en_progreso', 'resuelto'],
  solucion: null,
  vencido: false,
  fecha_vencimiento: '2030-01-01T13:00:00Z',
  creador: { id: 7, name: 'Camila Ríos' },
  asignado: null,
  cliente: null,
  created_at: '2026-10-26T13:00:00Z',
  updated_at: '2026-10-26T13:00:00Z',
})

const pagina = (elementos, total = elementos.length) => ({
  data: elementos,
  meta: { current_page: 1, last_page: 1, per_page: 15, total },
})

/** Peticiones de listado recibidas, para comprobar qué parámetros envió la interfaz. */
let listados = []

beforeEach(() => {
  listados = []
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('incidentes'), ({ request }) => {
      const parametros = new URL(request.url).searchParams
      listados.push(Object.fromEntries(parametros))
      return HttpResponse.json(
        parametros.get('per_page') === '1'
          ? pagina([incidente(1)], 42)
          : pagina([incidente(1), incidente(2), incidente(3)]),
      )
    }),
    manejador.get(api('incidentes/:id'), ({ params }) => HttpResponse.json({ data: incidente(Number(params.id)) })),
    manejador.get(api('incidentes/:id/historial'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('incidentes/:id/similares'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('personas'), () => HttpResponse.json({ data: [] })),
    manejador.get(api('clientes'), () => HttpResponse.json({ data: [], meta: { total: 0 } })),
    manejador.get(api('catalogos'), () =>
      HttpResponse.json({
        data: { estados: [], prioridades: ['baja', 'media', 'alta', 'critica'], categorias: ['red', 'otros'] },
      }),
    ),
  )
})

// Montan la aplicación completa: la primera carga de las vistas bajo demanda tarda unos segundos.
describe('incidentes', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  it('el listado muestra las filas y ordenar por un encabezado queda en la dirección', async () => {
    montada = await montarAplicacion('/incidentes')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Incidente 2'), { timeout: 8000 })

    const prioridad = montada.envoltorio.findAll('th').find((th) => th.text().includes('Prioridad'))
    expect(prioridad?.attributes('aria-sort')).toBe('none')
    await prioridad?.find('button').trigger('click')
    await esperarPintado()

    expect(montada.router.currentRoute.value.query).toMatchObject({ orden: 'prioridad', dir: 'desc' })
    await vi.waitFor(() => expect(listados.at(-1)).toMatchObject({ ordenar_por: 'prioridad', direccion: 'desc' }))
  })

  it('seleccionar filas despliega la barra de lote con las acciones permitidas', async () => {
    montada = await montarAplicacion('/incidentes')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Incidente 2'), { timeout: 8000 })

    await montada.envoltorio.find('input[aria-label="Seleccionar INC-000002"]').setValue(true)
    await esperarPintado()

    const lote = montada.envoltorio.find('[aria-label="Acciones en lote"]')
    expect(lote.text()).toContain('1 seleccionado')
    expect(lote.text()).toContain('Reasignar')
    // Sin el permiso borrar no se ofrece eliminar.
    expect(lote.text()).not.toContain('Eliminar')
  })

  it('eliminar en lote pide confirmación, y cancelar no elimina nada', async () => {
    let eliminaciones = 0
    servidor.use(
      manejador.get(api('me'), () =>
        HttpResponse.json({ data: { ...PERFIL, permisos: [...PERFIL.permisos, 'borrar'] } }),
      ),
      // La eliminación de verdad la comprueba el recorrido en el navegador: happy-dom no transmite bien un DELETE
      // con cuerpo.
      manejador.delete(api('incidentes/lote'), () => {
        eliminaciones += 1
        return new HttpResponse(null, { status: 204 })
      }),
    )
    montada = await montarAplicacion('/incidentes')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Incidente 2'), { timeout: 8000 })

    await montada.envoltorio.find('input[aria-label="Seleccionar INC-000001"]').setValue(true)
    await montada.envoltorio.find('input[aria-label="Seleccionar INC-000003"]').setValue(true)
    await esperarPintado()
    const lote = montada.envoltorio.find('[aria-label="Acciones en lote"]')
    await lote
      .findAll('button')
      .find((boton) => boton.text().includes('Eliminar'))
      ?.trigger('click')
    await esperarPintado()

    const dialogo = /** @type {HTMLElement} */ (document.querySelector('[role="alertdialog"]'))
    expect(dialogo.textContent).toContain('¿Eliminar 2 incidentes?')
    expect(eliminaciones).toBe(0)
    ;[...dialogo.querySelectorAll('button')].find((boton) => boton.textContent?.trim() === 'Cancelar')?.click()
    await esperarPintado()

    expect(eliminaciones).toBe(0)
    // La selección sigue: la persona puede elegir otra acción.
    expect(lote.text()).toContain('2 seleccionados')
  })

  it('abrir un incidente lo pone en una pestaña con su código', async () => {
    montada = await montarAplicacion('/incidentes/2')

    await vi.waitFor(
      () => expect(montada.envoltorio.find('[aria-label="Incidentes abiertos"]').text()).toContain('INC-000002'),
      {
        timeout: 8000,
      },
    )
    expect(montada.envoltorio.find('h1').text()).toBe('Incidente 2')
  })

  it('un incidente que ya no existe cierra su pestaña y vuelve al listado con aviso', async () => {
    servidor.use(
      manejador.get(api('incidentes/:id'), () =>
        HttpResponse.json({ status: 404, detail: 'No encontrado.' }, { status: 404 }),
      ),
    )
    montada = await montarAplicacion('/incidentes/9')

    await vi.waitFor(() => expect(montada.router.currentRoute.value.name).toBe('incidentes'), { timeout: 8000 })
    expect(document.body.textContent).toContain('no está disponible')
  })

  it('el formulario pone bajo su campo el error de la API y no pierde lo escrito', async () => {
    servidor.use(
      manejador.post(api('incidentes'), () =>
        HttpResponse.json(
          {
            status: 422,
            detail: 'Los datos no son válidos.',
            errors: { titulo: ['Ya existe un incidente abierto con ese título.'] },
          },
          { status: 422 },
        ),
      ),
    )
    montada = await montarAplicacion('/incidentes/nuevo')
    await vi.waitFor(() => expect(montada.envoltorio.find('form').exists()).toBe(true), { timeout: 8000 })

    const [titulo] = montada.envoltorio.findAll('input')
    await titulo.setValue('Sin internet')
    await montada.envoltorio.find('textarea').setValue('No hay conexión.')
    // El vencimiento se elige con el atajo «Mañana 8:00».
    await montada.envoltorio.find('.campo-fecha input').trigger('click')
    await esperarPintado()
    const atajos = [...document.querySelectorAll('button')]
    atajos.find((boton) => boton.textContent?.includes('Mañana 8:00'))?.click()
    await esperarPintado()

    await montada.envoltorio.find('form').trigger('submit')
    await vi.waitFor(
      () => expect(montada.envoltorio.text()).toContain('Ya existe un incidente abierto con ese título.'),
      {
        timeout: 8000,
      },
    )
    expect(/** @type {HTMLInputElement} */ (titulo.element).value).toBe('Sin internet')
  })
})
