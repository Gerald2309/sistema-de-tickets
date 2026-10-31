import { http as manejador, HttpResponse } from 'msw'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { esperarPintado, montarAplicacion } from '../../../../pruebas/montar.js'
import { api, servidor } from '../../../../pruebas/servidor.js'

const PERFIL = {
  id: 7,
  name: 'Camila Ríos',
  email: 'camila.rios@empresa-ejemplo.com',
  rol: { id: 3, nombre: 'Soporte N2' },
  cliente: null,
  permisos: [],
}

/** @param {import('@vue/test-utils').VueWrapper} envoltorio */
async function completarYEnviar(
  envoltorio,
  correo = 'camila.rios@empresa-ejemplo.com',
  contrasena = 'una contraseña larga',
) {
  const [campoCorreo, campoContrasena] = envoltorio.findAll('input')
  await campoCorreo.setValue(correo)
  await campoContrasena.setValue(contrasena)
  await envoltorio.find('form').trigger('submit')
  await esperarPintado(10)
}

// Montan la aplicación completa: la primera carga de las vistas bajo demanda tarda unos segundos.
describe('inicio de sesión', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => montada?.envoltorio.unmount())

  it('sin sesión, cualquier ruta lleva al inicio de sesión recordando el destino', async () => {
    montada = await montarAplicacion('/mi-cuenta')

    expect(montada.router.currentRoute.value.name).toBe('ingresar')
    expect(montada.router.currentRoute.value.query.destino).toBe('/mi-cuenta')
  })

  it('con credenciales válidas entra y vuelve a la ruta que se pidió', async () => {
    servidor.use(
      manejador.post(api('auth/login'), () => HttpResponse.json({ data: { token: '1|abc', expira_en: null } })),
      manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })),
    )
    montada = await montarAplicacion('/mi-cuenta')

    await completarYEnviar(montada.envoltorio)

    // La navegación carga la estructura de la aplicación bajo demanda.
    await vi.waitFor(() => expect(montada.router.currentRoute.value.name).toBe('mi-cuenta'), { timeout: 8000 })
  })

  it('con credenciales inválidas muestra un mensaje genérico y conserva el correo', async () => {
    servidor.use(
      manejador.post(api('auth/login'), () =>
        HttpResponse.json(
          {
            status: 422,
            detail: 'Los datos enviados no son válidos.',
            errors: { email: ['Estas credenciales no coinciden.'] },
          },
          { status: 422 },
        ),
      ),
    )
    montada = await montarAplicacion('/ingresar')

    await completarYEnviar(montada.envoltorio)

    const texto = montada.envoltorio.text()
    expect(texto).toContain('El correo o la contraseña no son correctos.')
    expect(texto).not.toContain('Estas credenciales no coinciden')
    const [campoCorreo, campoContrasena] = montada.envoltorio.findAll('input')
    expect(campoCorreo.element.value).toBe('camila.rios@empresa-ejemplo.com')
    expect(campoContrasena.element.value).toBe('')
  })

  it('tras el límite de intentos muestra la espera y bloquea el botón', async () => {
    servidor.use(
      manejador.post(api('auth/login'), () =>
        HttpResponse.json(
          { status: 429, detail: 'Demasiadas peticiones.' },
          { status: 429, headers: { 'Retry-After': '30' } },
        ),
      ),
    )
    montada = await montarAplicacion('/ingresar')

    await completarYEnviar(montada.envoltorio)

    expect(montada.envoltorio.text()).toContain('Podrá intentarlo de nuevo en 30 s.')
    expect(montada.envoltorio.find('button[type="submit"]').attributes('disabled')).toBeDefined()
  })

  it('no envía nada con el formulario incompleto', async () => {
    let enviado = false
    servidor.use(
      manejador.post(api('auth/login'), () => {
        enviado = true
        return HttpResponse.json({})
      }),
    )
    montada = await montarAplicacion('/ingresar')

    await completarYEnviar(montada.envoltorio, 'no-es-un-correo', '')

    expect(enviado).toBe(false)
    expect(montada.envoltorio.text()).toContain('Escriba un correo válido.')
  })
})
