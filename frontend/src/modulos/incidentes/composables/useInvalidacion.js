import { useQueryClient } from '@tanstack/vue-query'
import { claves } from '../api/claves.js'

/**
 * Qué se vuelve a pedir cuando cambian incidentes, sea por una escritura propia o por un aviso de tiempo real
 * (modelo-datos-cliente §4.3). No hay actualizaciones optimistas: el servidor decide transiciones y permisos,
 * así que se invalida lo afectado y la API responde con lo que la persona puede ver.
 * @param {import('@tanstack/vue-query').QueryClient} queryClient
 * @param {number[]} [ids] incidentes cambiados: su detalle, historial y similares
 * @returns {Promise<void>}
 */
export async function invalidarIncidentes(queryClient, ids = []) {
  await Promise.all([
    ...ids.map((id) => queryClient.invalidateQueries({ queryKey: claves.detalle(id) })),
    queryClient.invalidateQueries({ queryKey: claves.listas() }),
    queryClient.invalidateQueries({ queryKey: claves.conteos() }),
    queryClient.invalidateQueries({ queryKey: claves.panel }),
    queryClient.invalidateQueries({ queryKey: claves.avisos }),
    queryClient.invalidateQueries({ queryKey: claves.miDia }),
  ])
}

/** Invalidación tras escribir desde esta pestaña. */
export function useInvalidacion() {
  const queryClient = useQueryClient()

  /**
   * @param {number[]} [ids]
   * @returns {Promise<void>}
   */
  const trasCambiarIncidentes = (ids = []) => invalidarIncidentes(queryClient, ids)

  /**
   * @param {number[]} ids incidentes eliminados: su detalle se descarta
   * @returns {Promise<void>}
   */
  async function trasEliminarIncidentes(ids) {
    ids.forEach((id) => queryClient.removeQueries({ queryKey: claves.detalle(id) }))
    await invalidarIncidentes(queryClient)
  }

  return { trasCambiarIncidentes, trasEliminarIncidentes }
}
