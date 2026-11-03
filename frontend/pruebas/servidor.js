import { http as manejador, HttpResponse } from 'msw'
import { setupServer } from 'msw/node'

/** URL completa de un endpoint de la API, como la verá MSW. */
export const api = (ruta) => `${window.location.origin}/api/v1/${ruta}`

/**
 * Lo que pide toda pantalla con sesión, sin importar cuál: la campanita de la barra. Sobrevive a
 * resetHandlers(); una prueba que necesite otra respuesta la reemplaza con servidor.use(...).
 */
const comunes = [manejador.get(api('perfil/avisos'), () => HttpResponse.json({ data: [], meta: { sin_leer: 0 } }))]

/** Servidor de la API simulada. Cada prueba declara las respuestas que necesita con servidor.use(...). */
export const servidor = setupServer(...comunes)
