import { fileURLToPath, URL } from 'node:url'
import vue from '@vitejs/plugin-vue'
import { defineConfig } from 'vite'
import vuetify from 'vite-plugin-vuetify'
import { presupuestoDelPaquete } from './herramientas/presupuesto-del-paquete.js'
import { sinSecretosEnElPaquete } from './herramientas/sin-secretos-en-el-paquete.js'

// En desarrollo, el servidor de Vite reenvía la API, el WebSocket y la configuración a la aplicación de Docker
// (contenedor `web`, http://localhost:8000), así que trabaja contra la misma API que producción.
const destinoDesarrollo = process.env.DESTINO_DESARROLLO ?? 'http://localhost:8000'

export default defineConfig({
  plugins: [
    vue(),
    // Importa solo los componentes de Vuetify que se usan (presupuesto del paquete, RNF-F02).
    vuetify({ autoImport: true }),
    presupuestoDelPaquete({ limiteKb: 200, vistaInicial: 'VistaIngreso.vue' }),
    sinSecretosEnElPaquete(),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': { target: destinoDesarrollo, changeOrigin: true },
      '/app': { target: destinoDesarrollo, ws: true, changeOrigin: true },
      '/configuracion.json': { target: destinoDesarrollo, changeOrigin: true },
    },
  },
  build: {
    // Las dos últimas versiones de los navegadores principales (RNF-F26).
    target: ['chrome120', 'edge120', 'firefox121', 'safari17'],
    sourcemap: false,
  },
})
