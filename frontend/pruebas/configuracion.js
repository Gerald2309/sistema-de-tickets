// Configuración común de las pruebas unitarias y de componente.
import { afterAll, afterEach, beforeAll } from 'vitest'
import { servidor } from './servidor.js'

// happy-dom no implementa visualViewport, que los menús de Vuetify usan para ubicarse.
globalThis.visualViewport ??= /** @type {VisualViewport} */ (
  /** @type {unknown} */ ({
    width: window.innerWidth,
    height: window.innerHeight,
    offsetLeft: 0,
    offsetTop: 0,
    pageLeft: 0,
    pageTop: 0,
    scale: 1,
    addEventListener() {},
    removeEventListener() {},
  })
)

// La API se simula en la red (MSW): así se prueban también el cliente HTTP, sus interceptores y los
// adaptadores reales. Una petición sin respuesta simulada hace fallar la prueba.
beforeAll(() => servidor.listen({ onUnhandledRequest: 'error' }))
afterEach(() => {
  servidor.resetHandlers()
  sessionStorage.clear()
})
afterAll(() => servidor.close())
