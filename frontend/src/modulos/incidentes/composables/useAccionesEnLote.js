import { useMutation } from '@tanstack/vue-query'
import { computed } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { actualizarEnLote, eliminarEnLote } from '../api/incidentes.js'
import { useInvalidacion } from './useInvalidacion.js'
import { puedeMoverPlazos } from '../permisos.js'

export const MAXIMO_DEL_LOTE = 100

/**
 * Acciones en lote (RF-F24): todo o nada en el servidor. Solo se ofrecen las que los permisos permiten; un 422
 * nombra el incidente que impidió el cambio, y un 403 o 404 no revela más de lo que dice la API.
 * @param {() => number[]} idsElegidos
 * @param {() => void} alTerminar
 */
export function useAccionesEnLote(idsElegidos, alTerminar) {
  const sesion = useSesion()
  const notificaciones = useNotificaciones()
  const confirmacion = useConfirmacion()
  const { trasCambiarIncidentes, trasEliminarIncidentes } = useInvalidacion()

  const permite = computed(() => ({
    estado: sesion.tiene('cambiar_estado'),
    reasignar: sesion.tiene('reasignar'),
    clasificar: sesion.tieneAlguno('editar_todos', 'editar_propios'),
    moverPlazos: puedeMoverPlazos(sesion),
    eliminar: sesion.tiene('borrar'),
  }))

  /** @param {unknown} causa */
  function informar(causa) {
    notificaciones.error('No se aplicó ningún cambio', mensajeDeError(causa))
  }

  const cambio = useMutation({
    /** @param {Parameters<typeof actualizarEnLote>[1]} cambios */
    mutationFn: (cambios) => actualizarEnLote(idsElegidos(), cambios),
    onSuccess: async (actualizados) => {
      notificaciones.exito('Cambios aplicados', `${actualizados.length} incidentes actualizados.`)
      alTerminar()
      await trasCambiarIncidentes(actualizados.map((incidente) => incidente.id))
    },
    onError: informar,
  })

  const eliminacion = useMutation({
    mutationFn: () => eliminarEnLote(idsElegidos()),
    onSuccess: async () => {
      const ids = idsElegidos()
      notificaciones.exito('Incidentes eliminados', `${ids.length} incidentes eliminados.`)
      alTerminar()
      await trasEliminarIncidentes(ids)
    },
    onError: informar,
  })

  /**
   * @param {Parameters<typeof actualizarEnLote>[1]} cambios
   * @returns {Promise<void>}
   */
  async function aplicar(cambios) {
    await cambio.mutateAsync(cambios).catch(() => {})
  }

  /** @returns {Promise<void>} pide confirmación antes de eliminar */
  async function eliminar() {
    const cantidad = idsElegidos().length
    const confirmado = await confirmacion.pedir({
      titulo: `¿Eliminar ${cantidad} ${cantidad === 1 ? 'incidente' : 'incidentes'}?`,
      texto: 'Dejan de verse en el listado y su historial se conserva.',
      accion: 'Eliminar',
      peligrosa: true,
    })
    if (confirmado) {
      await eliminacion.mutateAsync().catch(() => {})
    }
  }

  return {
    permite,
    aplicar,
    eliminar,
    pendiente: computed(() => cambio.isPending.value || eliminacion.isPending.value),
  }
}
