import { useQueryClient } from '@tanstack/vue-query'
import { claves } from '../api/claves.js'

/**
 * Qué se vuelve a pedir tras escribir (modelo-datos-cliente §4.3). No hay actualizaciones optimistas: el
 * servidor decide transiciones y permisos, así que tras cada cambio se invalida lo afectado.
 */
export function useInvalidacion() {
  const queryClient = useQueryClient()

  /**
   * @param {number[]} [ids] incidentes cambiados (su detalle e historial)
   * @returns {Promise<void>}
   */
  async function trasCambiarIncidentes(ids = []) {
    await Promise.all([
      ...ids.map((id) => queryClient.invalidateQueries({ queryKey: claves.detalle(id) })),
      queryClient.invalidateQueries({ queryKey: claves.listas() }),
      queryClient.invalidateQueries({ queryKey: claves.conteos() }),
      queryClient.invalidateQueries({ queryKey: claves.panel }),
      queryClient.invalidateQueries({ queryKey: claves.avisos }),
    ])
  }

  /**
   * @param {number[]} ids incidentes eliminados: su detalle se descarta
   * @returns {Promise<void>}
   */
  async function trasEliminarIncidentes(ids) {
    ids.forEach((id) => queryClient.removeQueries({ queryKey: claves.detalle(id) }))
    await trasCambiarIncidentes()
  }

  return { trasCambiarIncidentes, trasEliminarIncidentes }
}
