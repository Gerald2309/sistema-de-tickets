import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { nextTick, ref } from 'vue'
import { useSesion } from '@/modulos/identidad/index.js'
import { conectarAplicacion } from './sesion-de-aplicacion.js'

/**
 * @param {() => Promise<unknown>} reemplazar lo que hace router.replace
 */
async function aplicacionCon(reemplazar) {
  setActivePinia(createPinia())
  const router = {
    currentRoute: ref({ name: 'incidente', fullPath: '/incidentes/5' }),
    replace: vi.fn(reemplazar),
    resolve: () => ({ href: '/ingresar?destino=%2Fincidentes%2F5' }),
  }
  const queryClient = { clear: vi.fn() }
  conectarAplicacion({ router: /** @type {any} */ (router), queryClient: /** @type {any} */ (queryClient) })
  const sesion = useSesion()
  // La sesión se abre en un tick y se cierra en otro: el observador ve los dos cambios.
  sesion.token = '1|abc'
  await nextTick()
  return { router, queryClient, sesion }
}

describe('salir de la sesión', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('va a ingresar y después borra la caché', async () => {
    const { router, queryClient, sesion } = await aplicacionCon(() => Promise.resolve())

    sesion.expirar()

    await vi.waitFor(() => expect(queryClient.clear).toHaveBeenCalled())
    expect(router.replace).toHaveBeenCalledWith(expect.objectContaining({ name: 'ingresar' }))
  })

  it('si no puede llegar a ingresar, carga esa página desde cero: no quedan datos a la vista', async () => {
    const asignar = vi.fn()
    vi.stubGlobal('location', { ...window.location, assign: asignar })
    const { queryClient, sesion } = await aplicacionCon(() =>
      Promise.reject(new TypeError('Failed to fetch dynamically imported module')),
    )

    sesion.expirar()

    await vi.waitFor(() => expect(asignar).toHaveBeenCalledWith('/ingresar?destino=%2Fincidentes%2F5'))
    expect(queryClient.clear).not.toHaveBeenCalled()
  })
})
