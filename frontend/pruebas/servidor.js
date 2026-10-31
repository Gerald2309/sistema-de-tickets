import { setupServer } from 'msw/node'

/** Servidor de la API simulada. Cada prueba declara las respuestas que necesita con servidor.use(...). */
export const servidor = setupServer()

/** URL completa de un endpoint de la API, como la verá MSW. */
export const api = (ruta) => `${window.location.origin}/api/v1/${ruta}`
