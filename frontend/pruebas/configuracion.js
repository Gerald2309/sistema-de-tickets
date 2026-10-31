// Configuración común de las pruebas unitarias y de componente.
import { afterAll, afterEach, beforeAll } from 'vitest'
import { servidor } from './servidor.js'

// La API se simula en la red (MSW): así se prueban también el cliente HTTP, sus interceptores y los
// adaptadores reales. Una petición sin respuesta simulada hace fallar la prueba.
beforeAll(() => servidor.listen({ onUnhandledRequest: 'error' }))
afterEach(() => {
  servidor.resetHandlers()
  sessionStorage.clear()
})
afterAll(() => servidor.close())
