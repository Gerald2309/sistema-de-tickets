import { QueryClient } from '@tanstack/vue-query'
import { ErrorDeApi } from '@/compartido/api/errores.js'

/**
 * Caché del estado del servidor (modelo-datos-cliente.md §4). Cada consulta puede ajustar su vigencia; estos
 * son los valores por defecto.
 * @returns {QueryClient}
 */
export function crearClienteDeConsultas() {
  return new QueryClient({
    defaultOptions: {
      queries: {
        staleTime: 15_000,
        gcTime: 5 * 60_000,
        refetchOnWindowFocus: true,
        refetchOnReconnect: true,
        // Solo las lecturas se reintentan, como mucho dos veces y nunca ante un 4xx (RNF-F23).
        retry: (fallos, error) =>
          fallos < 2 && !(error instanceof ErrorDeApi && error.estado >= 400 && error.estado < 500),
        retryDelay: (intento) => Math.min(1000 * 2 ** intento, 8000),
      },
      mutations: { retry: false },
    },
  })
}
