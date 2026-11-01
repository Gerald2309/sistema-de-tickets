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
    manejador.get(api('incidentes/:id/mensajes'), () => HttpResponse.json({ data: [] })),
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

  it('en un incidente ajeno que atiende, lo clasifica desde las propiedades', async () => {
    /** @type {unknown} */
    let recibido
    const ajeno = {
      ...incidente(4),
      creador: { id: 30, name: 'Úrsula Apodaca' },
      asignado: { id: 7, name: 'Camila Ríos' },
    }
    servidor.use(
      manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: ajeno })),
      manejador.patch(api('incidentes/:id'), async ({ request }) => {
        recibido = await request.json()
        return HttpResponse.json({ data: { ...ajeno, prioridad: 'critica' } })
      }),
    )
    montada = await montarAplicacion('/incidentes/4')
    const envoltorio = montada.envoltorio
    await vi.waitFor(() => expect(envoltorio.find('h1').text()).toBe('Incidente 4'), { timeout: 8000 })

    expect(envoltorio.findAll('a').some((enlace) => enlace.text() === 'Editar')).toBe(false)
    expect(envoltorio.find('[aria-labelledby="titulo-propiedades"]').text()).toContain('cambiar categoría')

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text().includes('cambiar prioridad'))
      ?.trigger('click')
    await esperarPintado()
    const critica = [...document.querySelectorAll('.v-list-item')].find(
      (item) => item.textContent?.trim() === 'Crítica',
    )
    if (critica instanceof HTMLElement) {
      critica.click()
    }

    await vi.waitFor(() => expect(recibido).toEqual({ prioridad: 'critica' }))
    await vi.waitFor(() => expect(document.body.textContent).toContain('Cambios guardados'))
  })

  it('soporte no corrige el relato: el detalle ofrece asignar, no editar', async () => {
    montada = await montarAplicacion('/incidentes/5')
    await vi.waitFor(() => expect(montada.envoltorio.find('h1').text()).toBe('Incidente 5'), { timeout: 8000 })

    expect(montada.envoltorio.text()).not.toContain('Editar')
    const asignacion = montada.envoltorio.find('[aria-labelledby="titulo-asignacion"]')
    expect(asignacion.findAll('button').some((boton) => /Asignar|Reasignar/.test(boton.text()))).toBe(true)
  })

  it('la conversación marca las notas internas y envía lo escrito', async () => {
    const mensaje = (id, cuerpo, interno, autor) => ({
      id,
      cuerpo,
      interno,
      autor,
      adjuntos: [],
      created_at: '2026-10-26T13:00:00Z',
    })
    /** @type {FormData | null} */
    let enviado = null
    servidor.use(
      manejador.get(api('incidentes/:id/mensajes'), () =>
        HttpResponse.json({
          data: [
            mensaje(1, 'No imprime.', false, { id: 7, name: 'Camila Ríos' }),
            mensaje(2, 'Revisar el controlador.', true, { id: 9, name: 'Luis Pardo' }),
          ],
        }),
      ),
      manejador.post(api('incidentes/:id/mensajes'), async ({ request }) => {
        enviado = await request.formData()
        return HttpResponse.json(
          { data: mensaje(3, '¿Ya reinició?', false, { id: 7, name: 'Camila Ríos' }) },
          { status: 201 },
        )
      }),
    )
    montada = await montarAplicacion('/incidentes/2')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Revisar el controlador.'), { timeout: 8000 })

    expect(montada.envoltorio.find('.mensaje--interno').text()).toContain('Nota interna')
    expect(montada.envoltorio.find('.mensaje--propio').text()).toContain('No imprime.')

    await montada.envoltorio.find('form.redactor textarea').setValue('¿Ya reinició?')
    await montada.envoltorio.find('form.redactor').trigger('submit')

    await vi.waitFor(() => expect(enviado?.get('cuerpo')).toBe('¿Ya reinició?'))
    expect(enviado?.has('interno')).toBe(false)
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

  it('el formulario pone bajo su campo el error de la API, no pierde lo escrito y reintenta con la misma clave', async () => {
    /** @type {(string | null)[]} */
    const claves = []
    servidor.use(
      manejador.post(api('incidentes'), ({ request }) => {
        claves.push(request.headers.get('Idempotency-Key'))
        return HttpResponse.json(
          {
            status: 422,
            detail: 'Los datos no son válidos.',
            errors: { titulo: ['Ya existe un incidente abierto con ese título.'] },
          },
          { status: 422 },
        )
      }),
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

    // Corregido y reenviado, el mismo formulario repite la clave: si el primer envío hubiera llegado, no se duplica.
    await titulo.setValue('Sin internet en el piso 3')
    await montada.envoltorio.find('form').trigger('submit')
    await vi.waitFor(() => expect(claves).toHaveLength(2))
    expect(claves[0]).toMatch(/^[0-9a-f-]{36}$/)
    expect(claves[1]).toBe(claves[0])
  })

  it('el formulario propone el plazo del acuerdo y, si no se cambia, deja que la API lo calcule', async () => {
    /** @type {Record<string, unknown> | undefined} */
    let recibido
    servidor.use(
      manejador.get(api('plazo-propuesto'), () =>
        HttpResponse.json({
          data: { vence_respuesta_en: '2030-01-01T09:00:00Z', fecha_vencimiento: '2030-01-02T09:00:00Z' },
        }),
      ),
      manejador.post(api('incidentes'), async ({ request }) => {
        recibido = /** @type {Record<string, unknown>} */ (await request.json())
        return HttpResponse.json({ data: incidente(9) }, { status: 201 })
      }),
      manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: incidente(9) })),
    )
    montada = await montarAplicacion('/incidentes/nuevo')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('Según el acuerdo de servicio'), {
      timeout: 8000,
    })

    const [titulo] = montada.envoltorio.findAll('input')
    await titulo.setValue('Sin internet')
    await montada.envoltorio.find('textarea').setValue('No hay conexión.')
    await montada.envoltorio.find('form').trigger('submit')

    await vi.waitFor(() => expect(recibido).toBeDefined(), { timeout: 8000 })
    expect(recibido).not.toHaveProperty('fecha_vencimiento')
  })

  it('el solicitante elige hasta prioridad alta, ve el aviso de la fecha requerida y no elige el vencimiento', async () => {
    /** @type {Record<string, unknown> | undefined} */
    let recibido
    servidor.use(
      manejador.get(api('me'), () =>
        HttpResponse.json({ data: { ...PERFIL, permisos: ['ver_propios', 'editar_propios', 'crear_incidente'] } }),
      ),
      manejador.post(api('incidentes'), async ({ request }) => {
        recibido = /** @type {Record<string, unknown>} */ (await request.json())
        return HttpResponse.json({ data: incidente(9) }, { status: 201 })
      }),
      manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: incidente(9) })),
    )
    montada = await montarAplicacion('/incidentes/nuevo')
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('¿Para cuándo lo necesita?'), { timeout: 8000 })

    expect(montada.envoltorio.text()).toContain('Es una referencia, no la fecha final.')
    expect(montada.envoltorio.text()).not.toContain('Fecha de vencimiento')

    const [titulo] = montada.envoltorio.findAll('input')
    await titulo.setValue('Sin internet')
    await montada.envoltorio.find('textarea').setValue('No hay conexión.')
    await montada.envoltorio.find('form').trigger('submit')

    await vi.waitFor(() => expect(recibido).toBeDefined(), { timeout: 8000 })
    expect(recibido).not.toHaveProperty('fecha_vencimiento')
    expect(recibido).not.toHaveProperty('fecha_requerida')
  })

  it('el detalle avisa a soporte cuando piden el incidente para antes del plazo', async () => {
    const pedido = {
      ...incidente(4),
      creador: { id: 30, name: 'Úrsula Apodaca' },
      fecha_vencimiento: '2030-01-05T13:00:00Z',
      fecha_requerida: '2030-01-02T13:00:00Z',
    }
    servidor.use(manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: pedido })))
    montada = await montarAplicacion('/incidentes/4')

    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain('La necesita para'), { timeout: 8000 })
    expect(montada.envoltorio.text()).toContain('antes del plazo')
  })

  it('el detalle muestra a soporte la cuenta regresiva de la primera respuesta y de la resolución', async () => {
    const conPlazos = {
      ...incidente(4),
      creador: { id: 30, name: 'Úrsula Apodaca' },
      vence_respuesta_en: '2020-01-01T13:00:00Z',
      primera_respuesta_en: null,
      resuelto_en: null,
    }
    servidor.use(manejador.get(api('incidentes/:id'), () => HttpResponse.json({ data: conPlazos })))
    montada = await montarAplicacion('/incidentes/4')

    await vi.waitFor(() => expect(montada.envoltorio.find('.plazos').exists()).toBe(true), { timeout: 8000 })
    const [respuesta, resolucion] = montada.envoltorio.findAll('.plazo')
    expect(respuesta.text()).toContain('Primera respuesta')
    expect(respuesta.text()).toContain('Vencido hace')
    expect(respuesta.classes()).toContain('plazo--incumplido')
    expect(resolucion.text()).toContain('Quedan')
  })

  it('asignar ofrece a las personas agrupadas por nivel, con el nombre de cada grupo', async () => {
    servidor.use(
      manejador.get(api('personas'), () =>
        HttpResponse.json({
          data: [
            { id: 11, name: 'Cristina Guillen', activo: true, rol: 'Soporte N1' },
            { id: 12, name: 'Diego Betancourt', activo: true, rol: 'Soporte N2' },
          ],
        }),
      ),
    )
    montada = await montarAplicacion('/incidentes/5')
    await vi.waitFor(() => expect(montada.envoltorio.find('h1').text()).toBe('Incidente 5'), { timeout: 8000 })

    await montada.envoltorio
      .find('[aria-labelledby="titulo-asignacion"]')
      .findAll('button')
      .find((boton) => /Asignar/.test(boton.text()))
      ?.trigger('click')
    // El buscador abre su lista al pulsarlo.
    await vi.waitFor(() => expect(document.querySelector('.reasignar .v-field')).not.toBeNull())
    const campo = /** @type {HTMLElement} */ (document.querySelector('.reasignar .v-field'))
    campo.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }))
    campo.click()

    await vi.waitFor(() => expect(document.body.textContent).toContain('Cristina Guillen'))
    expect(document.body.textContent).toContain('Soporte N1')
    expect(document.body.textContent).toContain('Soporte N2')
    expect(document.body.textContent).not.toContain('[object Object]')
  })
})
