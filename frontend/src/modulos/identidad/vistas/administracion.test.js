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
  permisos: ['administrar_usuarios', 'administrar_roles', 'ver_auditoria'],
}

const ROLES = [
  { id: 1, nombre: 'Administrador', permisos: ['administrar_roles', 'administrar_usuarios'], usuarios: 2 },
  { id: 4, nombre: 'Solicitante', permisos: ['crear_incidente', 'ver_propios'], usuarios: 0 },
]

const USUARIOS = [
  {
    id: 1,
    name: 'Ana Gómez',
    email: 'ana.gomez@psicol.co',
    activo: true,
    rol: { id: 1, nombre: 'Administrador' },
    cliente: null,
  },
  {
    id: 9,
    name: 'Luis Pardo',
    email: 'luis.pardo@empresa-ejemplo.com',
    activo: false,
    rol: { id: 4, nombre: 'Solicitante' },
    cliente: { id: 3, nombre: 'Andina Seguros' },
  },
]

const pagina = (elementos, total = elementos.length) => ({
  data: elementos,
  meta: { current_page: 1, last_page: 1, per_page: 15, total },
})

/** Cuerpos recibidos por la API simulada, por endpoint. */
let recibidos = {}
beforeEach(() => {
  recibidos = {}
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(
    manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    manejador.get(api('roles'), () => HttpResponse.json({ data: ROLES })),
    manejador.get(api('permisos'), () =>
      HttpResponse.json({
        data: ['crear_incidente', 'ver_propios', 'ver_todos', 'administrar_roles', 'administrar_usuarios'].map(
          (clave) => ({ clave }),
        ),
      }),
    ),
    manejador.get(api('usuarios'), ({ request }) => {
      const activo = new URL(request.url).searchParams.get('activo')
      const elegidos = activo === null ? USUARIOS : USUARIOS.filter((u) => Number(u.activo) === Number(activo))
      return HttpResponse.json(pagina(elegidos))
    }),
    manejador.get(api('clientes'), () =>
      HttpResponse.json(pagina([{ id: 3, nombre: 'Andina Seguros', activo: true, usuarios: 4 }])),
    ),
  )
})

// Montan la aplicación completa: la primera carga de las vistas bajo demanda tarda unos segundos.
describe('administración', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  /** @param {string} ruta @param {string} texto */
  async function abrir(ruta, texto) {
    montada = await montarAplicacion(ruta)
    await vi.waitFor(() => expect(montada.envoltorio.text()).toContain(texto), { timeout: 8000 })
    return montada.envoltorio
  }

  it('usuarios: la lista muestra rol, empresa y estado, con el total de cada vista', async () => {
    const envoltorio = await abrir('/administracion/usuarios', 'Luis Pardo')

    const filas = envoltorio
      .findAll('table tr')
      .filter((fila) => fila.find('td').exists())
      .map((fila) => fila.text())
    expect(filas[0]).toContain('Personal de Psicol')
    expect(filas[1]).toContain('Andina Seguros')
    expect(filas[1]).toContain('Inactivo')
    await vi.waitFor(() =>
      expect(envoltorio.find('[aria-label="Estado de las cuentas"]').text()).toMatch(/Activos\s*1/),
    )
  })

  it('usuarios: crear envía el correo en minúsculas y «Personal de Psicol» como sin empresa', async () => {
    servidor.use(
      manejador.post(api('usuarios'), async ({ request }) => {
        recibidos.usuario = await request.json()
        return HttpResponse.json({ data: { ...USUARIOS[1], id: 20, name: 'Marta Ruiz' } }, { status: 201 })
      }),
    )
    const envoltorio = await abrir('/administracion/usuarios', 'Luis Pardo')

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text() === 'Nuevo usuario')
      ?.trigger('click')
    await esperarPintado()
    const panel = envoltorio.find('#formulario-usuario')
    const [nombre, correo] = panel.findAll('input[type="text"], input[type="email"]')
    await nombre.setValue('Marta Ruiz')
    await correo.setValue('  Marta.Ruiz@Psicol.co ')
    await panel.find('input[type="password"]').setValue('una contraseña inicial larga')
    await vi.waitFor(() =>
      expect(envoltorio.find('button[form="formulario-usuario"]').attributes('disabled')).toBeUndefined(),
    )
    await panel.trigger('submit')

    await vi.waitFor(() => expect(recibidos.usuario).toBeDefined())
    expect(recibidos.usuario).toEqual({
      name: 'Marta Ruiz',
      email: 'marta.ruiz@psicol.co',
      password: 'una contraseña inicial larga',
      rol_id: 4,
      cliente_id: null,
    })
    await vi.waitFor(() => expect(document.body.textContent).toContain('Usuario creado'))
  })

  it('usuarios: el error de la API queda bajo su campo', async () => {
    servidor.use(
      manejador.post(api('usuarios'), () =>
        HttpResponse.json(
          { status: 422, detail: 'Los datos no son válidos.', errors: { email: ['Ese correo ya está registrado.'] } },
          { status: 422 },
        ),
      ),
    )
    const envoltorio = await abrir('/administracion/usuarios', 'Luis Pardo')
    await envoltorio
      .findAll('button')
      .find((boton) => boton.text() === 'Nuevo usuario')
      ?.trigger('click')
    await esperarPintado()
    const panel = envoltorio.find('#formulario-usuario')
    const [nombre, correo] = panel.findAll('input[type="text"], input[type="email"]')
    await nombre.setValue('Ana Gómez')
    await correo.setValue('ana.gomez@psicol.co')
    await panel.find('input[type="password"]').setValue('una contraseña inicial larga')
    await vi.waitFor(() =>
      expect(envoltorio.find('button[form="formulario-usuario"]').attributes('disabled')).toBeUndefined(),
    )
    await panel.trigger('submit')

    await vi.waitFor(() => expect(panel.text()).toContain('Ese correo ya está registrado.'))
  })

  /**
   * Abre el menú de la fila y elige una opción (el menú se monta fuera de la aplicación, en el cuerpo).
   * @param {import('@vue/test-utils').VueWrapper} envoltorio
   * @param {string} persona
   * @param {string} opcion
   */
  async function elegirEnFila(envoltorio, persona, opcion) {
    await envoltorio.find(`button[aria-label="Acciones de ${persona}"]`).trigger('click')
    await esperarPintado()
    const elemento = [...document.querySelectorAll('.v-list-item')].find((item) => item.textContent?.trim() === opcion)
    if (elemento instanceof HTMLElement) {
      elemento.click()
    }
    await esperarPintado()
  }

  it('usuarios: desactivar pregunta qué pasa con sus incidentes sin resolver y, si la API lo impide, explica por qué', async () => {
    /** @type {unknown[]} */
    const recibidos = []
    servidor.use(
      manejador.get(api('usuarios/:id/incidentes-abiertos'), () => HttpResponse.json({ data: { total: 3 } })),
      manejador.post(api('usuarios/:id/incidentes-abiertos/traspaso'), async ({ request }) => {
        recibidos.push({ traspaso: await request.json() })
        return HttpResponse.json({ data: { traspasados: 3 } })
      }),
      manejador.patch(api('usuarios/:id'), async ({ request }) => {
        recibidos.push(await request.json())
        return HttpResponse.json(
          {
            status: 422,
            detail: 'Los datos no son válidos.',
            errors: { activo: ['No se puede desactivar al último administrador activo.'] },
          },
          { status: 422 },
        )
      }),
    )
    const envoltorio = await abrir('/administracion/usuarios', 'Luis Pardo')

    await elegirEnFila(envoltorio, 'Ana Gómez', 'Desactivar')
    await vi.waitFor(() => expect(document.body.textContent).toContain('¿Desactivar a Ana Gómez?'))
    await vi.waitFor(() => expect(document.body.textContent).toContain('sin resolver'))
    expect(document.body.textContent).toContain('Devolverlos a la cola sin asignar')
    const dialogo = /** @type {HTMLElement} */ (document.querySelector('.dialogo-desactivar'))
    ;[...dialogo.querySelectorAll('button')].find((boton) => boton.textContent?.trim() === 'Desactivar')?.click()

    // Por defecto vuelven a la cola, y después se desactiva.
    await vi.waitFor(() => expect(recibidos).toEqual([{ traspaso: { usuario_asignado_id: null } }, { activo: false }]))
    await vi.waitFor(() =>
      expect(document.body.textContent).toContain('No se puede desactivar al último administrador activo.'),
    )
  })

  it('usuarios: reactivar no pide confirmación', async () => {
    /** @type {unknown} */
    let recibido
    servidor.use(
      manejador.patch(api('usuarios/:id'), async ({ request }) => {
        recibido = await request.json()
        return HttpResponse.json({ data: { ...USUARIOS[1], activo: true } })
      }),
    )
    const envoltorio = await abrir('/administracion/usuarios', 'Luis Pardo')

    await elegirEnFila(envoltorio, 'Luis Pardo', 'Reactivar')

    await vi.waitFor(() => expect(recibido).toEqual({ activo: true }))
    await vi.waitFor(() => expect(document.body.textContent).toContain('Luis Pardo puede volver a iniciar sesión.'))
  })

  it('roles: los permisos se editan en borrador y se guardan de una vez, ordenados', async () => {
    servidor.use(
      manejador.put(api('roles/:id/permisos'), async ({ request, params }) => {
        recibidos.permisos = { id: Number(params.id), ...(await request.json()) }
        return HttpResponse.json({ data: { ...ROLES[1], permisos: recibidos.permisos.permisos } })
      }),
    )
    const envoltorio = await abrir('/administracion/roles?rol=4', 'Solicitante')

    const interruptor = envoltorio.find('input[role="switch"][aria-label="Ver todos"]')
    expect(interruptor.exists()).toBe(true)
    await interruptor.setValue(true)
    await esperarPintado()
    expect(envoltorio.text()).toContain('1 cambio sin guardar')

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text().includes('Guardar permisos'))
      ?.trigger('click')
    await vi.waitFor(() => expect(recibidos.permisos).toBeDefined())
    expect(recibidos.permisos).toEqual({ id: 4, permisos: ['crear_incidente', 'ver_propios', 'ver_todos'] })
    await vi.waitFor(() => expect(document.body.textContent).toContain('Permisos guardados'))
  })

  it('roles: un rol con personas no se puede eliminar y el motivo se ve', async () => {
    const envoltorio = await abrir('/administracion/roles?rol=1', 'Tiene 2 personas')

    const eliminar = envoltorio.findAll('button').find((boton) => boton.text() === 'Eliminar')
    expect(eliminar?.attributes('disabled')).toBeDefined()
  })

  it('empresas: crear una empresa envía solo su nombre', async () => {
    servidor.use(
      manejador.post(api('clientes'), async ({ request }) => {
        recibidos.empresa = await request.json()
        return HttpResponse.json(
          { data: { id: 8, nombre: 'Café del Valle', activo: true, usuarios: 0 } },
          { status: 201 },
        )
      }),
    )
    const envoltorio = await abrir('/administracion/empresas', 'Andina Seguros')

    await envoltorio
      .findAll('button')
      .find((boton) => boton.text() === 'Nueva empresa')
      ?.trigger('click')
    await esperarPintado()
    const campo = /** @type {HTMLInputElement} */ (document.querySelector('.dialogo-texto input'))
    campo.value = '  Café del Valle '
    campo.dispatchEvent(new Event('input'))
    await esperarPintado()
    document.querySelector('.dialogo-texto form')?.dispatchEvent(new Event('submit'))

    await vi.waitFor(() => expect(recibidos.empresa).toEqual({ nombre: 'Café del Valle' }))
  })
})
