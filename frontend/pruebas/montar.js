import { VueQueryPlugin } from '@tanstack/vue-query'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory } from 'vue-router'
import { desplazable } from '@/compartido/accesibilidad.js'
import { revelar } from '@/compartido/movimiento.js'
import { crearEnrutador } from '@/nucleo/enrutador/index.js'
import { crearClienteDeConsultas } from '@/nucleo/plugins/consultas.js'
import { crearVuetify } from '@/nucleo/plugins/vuetify.js'
import { conectarAplicacion } from '@/nucleo/sesion-de-aplicacion.js'

/**
 * Monta la aplicación real (enrutador, sesión, Vuetify, caché) en una ruta, con el historial en memoria.
 * La API se simula con MSW (pruebas/servidor.js).
 * @param {string} ruta
 */
export async function montarAplicacion(ruta = '/') {
  const pinia = createPinia()
  setActivePinia(pinia)
  const queryClient = crearClienteDeConsultas()
  queryClient.setDefaultOptions({ queries: { retry: false } })
  const router = crearEnrutador(createMemoryHistory())
  conectarAplicacion({ router, queryClient })

  const App = (await import('@/App.vue')).default
  const contenedor = document.createElement('div')
  document.body.appendChild(contenedor)
  router.push(ruta)
  await router.isReady()

  const envoltorio = mount(App, {
    attachTo: contenedor,
    global: {
      plugins: [pinia, crearVuetify(), [VueQueryPlugin, { queryClient }], router],
      directives: { revelar, desplazable },
    },
  })
  return { envoltorio, router, queryClient, pinia }
}

/**
 * Espera a que terminen las promesas pendientes y Vue pinte.
 * @param {number} [veces]
 */
export async function esperarPintado(veces = 5) {
  for (let i = 0; i < veces; i++) {
    await new Promise((resolver) => setTimeout(resolver, 0))
  }
}
