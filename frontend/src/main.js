import '@fontsource/plus-jakarta-sans/latin-400.css'
import '@fontsource/plus-jakarta-sans/latin-500.css'
import '@fontsource/plus-jakarta-sans/latin-600.css'
import '@fontsource/plus-jakarta-sans/latin-700.css'
import '@fontsource/plus-jakarta-sans/latin-800.css'
import '@/compartido/estilos/tokens.css'
import '@/compartido/estilos/base.css'
import '@/compartido/estilos/disposicion.css'
import '@/compartido/estilos/movimiento.css'

import { VueQueryPlugin } from '@tanstack/vue-query'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import { revelar } from '@/compartido/movimiento.js'
import { crearEnrutador } from '@/nucleo/enrutador/index.js'
import { crearClienteDeConsultas } from '@/nucleo/plugins/consultas.js'
import { crearVuetify } from '@/nucleo/plugins/vuetify.js'
import { conectarTiempoReal } from '@/nucleo/plugins/tiempo-real.js'
import { alTerminarLaSesion, conectarAplicacion } from '@/nucleo/sesion-de-aplicacion.js'

// Raíz de composición: aquí se crean y se unen las piezas; el resto de la aplicación solo las usa.
const aplicacion = createApp(App)
const queryClient = crearClienteDeConsultas()
const router = crearEnrutador()

aplicacion.use(createPinia())
aplicacion.use(crearVuetify())
aplicacion.use(VueQueryPlugin, { queryClient })
aplicacion.use(router)
aplicacion.directive('revelar', revelar)

conectarAplicacion({ router, queryClient })
// Sin esperar: la aplicación funciona igual sin WebSocket (refresco periódico de respaldo).
conectarTiempoReal({ queryClient, alTerminarLaSesion })

aplicacion.config.errorHandler = (/** @type {unknown} */ error) => {
  // En producción no se muestran detalles; la pantalla afectada presenta su propio mensaje.
  if (import.meta.env.DEV) {
    console.error(error)
  }
}

aplicacion.mount('#app')
