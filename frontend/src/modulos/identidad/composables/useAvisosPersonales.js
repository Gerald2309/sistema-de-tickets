import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { refrescoDeRespaldo } from '@/compartido/tiempo-real.js'
import { listarAvisos, marcarAvisoLeido, marcarTodosLosAvisosLeidos } from '../api/avisos.js'
import { clavesDeIdentidad } from '../api/claves.js'
import { useSesion } from '../tienda/sesion.js'

// Sin WebSocket, la campanita se pone al día cada minuto.
const RESPALDO_MS = 60_000

/**
 * Los avisos de la campanita. Llegan al instante por el tiempo real (invalidarAvisosPersonales); sin él, con el
 * refresco de respaldo.
 */
export function useAvisosPersonales() {
  const sesion = useSesion()
  const queryClient = useQueryClient()

  const consulta = useQuery({
    queryKey: clavesDeIdentidad.avisosPersonales(),
    queryFn: ({ signal }) => listarAvisos(signal),
    enabled: computed(() => !!sesion.perfil && !sesion.debeCambiarContrasena),
    refetchInterval: refrescoDeRespaldo(RESPALDO_MS),
  })

  const alCambiar = () => queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.avisosPersonales() })
  const leerUno = useMutation({ mutationFn: marcarAvisoLeido, onSettled: alCambiar })
  const leerTodos = useMutation({ mutationFn: marcarTodosLosAvisosLeidos, onSettled: alCambiar })

  return {
    avisos: computed(() => consulta.data.value?.avisos ?? []),
    sinLeer: computed(() => consulta.data.value?.sinLeer ?? 0),
    /** @param {string} id */
    marcarLeido: (id) => leerUno.mutateAsync(id),
    marcarTodos: () => leerTodos.mutateAsync(),
  }
}

/**
 * Para el tiempo real: llegó un aviso nuevo y la campanita lo vuelve a pedir.
 * @param {import('@tanstack/vue-query').QueryClient} queryClient
 * @returns {Promise<void>}
 */
export function invalidarAvisosPersonales(queryClient) {
  return queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.avisosPersonales() })
}
