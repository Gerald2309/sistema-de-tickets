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
  permisos: [],
}

beforeEach(() => {
  sessionStorage.setItem('mesa-de-ayuda.sesion', JSON.stringify({ token: '1|abc', expiraEn: null }))
  servidor.use(manejador.get(api('me'), () => HttpResponse.json({ data: PERFIL })))
})

describe('mi cuenta: cambio de contraseña', { timeout: 20_000 }, () => {
  let montada
  afterEach(() => {
    montada?.envoltorio.unmount()
    sessionStorage.clear()
  })

  /** @param {string} actual @param {string} nueva @param {string} [repetida] */
  async function completar(actual, nueva, repetida = nueva) {
    montada = await montarAplicacion('/mi-cuenta')
    await vi.waitFor(() => expect(montada.envoltorio.findAll('input[type="password"]')).toHaveLength(3), {
      timeout: 8000,
    })
    const [campoActual, campoNueva, campoRepetida] = montada.envoltorio.findAll('input[type="password"]')
    await campoActual.setValue(actual)
    await campoNueva.setValue(nueva)
    await campoRepetida.setValue(repetida)
    await esperarPintado()
    return montada.envoltorio.findAll('form').find((form) => form.text().includes('Cambiar contraseña'))
  }

  it('si la repetición no coincide, no se puede enviar', async () => {
    const formulario = await completar('la contraseña de antes', 'una contraseña nueva', 'otra distinta')

    const boton = formulario?.findAll('button').find((b) => b.text().includes('Cambiar contraseña'))
    expect(boton?.attributes('disabled')).toBeDefined()
  })

  it('envía la actual y la nueva, y vacía los campos al terminar', async () => {
    /** @type {unknown} */
    let recibido
    servidor.use(
      manejador.put(api('perfil/contrasena'), async ({ request }) => {
        recibido = await request.json()
        return new HttpResponse(null, { status: 204 })
      }),
    )
    const formulario = await completar('la contraseña de antes', 'una contraseña nueva')
    await formulario?.trigger('submit')

    await vi.waitFor(() =>
      expect(recibido).toEqual({ password_actual: 'la contraseña de antes', password: 'una contraseña nueva' }),
    )
    await vi.waitFor(() => expect(document.body.textContent).toContain('Contraseña actualizada'))
    const campos = montada.envoltorio.findAll('input[type="password"]')
    expect(campos.map((campo) => /** @type {HTMLInputElement} */ (campo.element).value)).toEqual(['', '', ''])
  })

  it('si la contraseña actual no es correcta, el error queda bajo ese campo', async () => {
    servidor.use(
      manejador.put(api('perfil/contrasena'), () =>
        HttpResponse.json(
          {
            status: 422,
            detail: 'Los datos no son válidos.',
            errors: { password_actual: ['La contraseña actual no es correcta.'] },
          },
          { status: 422 },
        ),
      ),
    )
    const formulario = await completar('una equivocada', 'una contraseña nueva')
    await formulario?.trigger('submit')

    await vi.waitFor(() => expect(formulario?.text()).toContain('La contraseña actual no es correcta.'))
  })
})
