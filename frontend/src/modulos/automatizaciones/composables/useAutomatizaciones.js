import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { cambiarEstado, eliminarAutomatizacion, listarAutomatizaciones } from '../api/automatizaciones.js'
import { clavesDeAutomatizaciones } from '../api/claves.js'

/** @typedef {import('../tipos.js').Automatizacion} Automatizacion */

/** La lista de automatizaciones: encenderlas, apagarlas y eliminarlas desde ahí. */
export function useAutomatizaciones() {
  const queryClient = useQueryClient()
  const notificaciones = useNotificaciones()

  const lista = useQuery({
    queryKey: clavesDeAutomatizaciones.todas(),
    queryFn: ({ signal }) => listarAutomatizaciones(signal),
  })

  const trasCambiar = () => queryClient.invalidateQueries({ queryKey: clavesDeAutomatizaciones.todas() })

  const estado = useMutation({
    /** @param {Automatizacion} automatizacion */
    mutationFn: (automatizacion) => cambiarEstado(automatizacion.id, !automatizacion.activa),
    onSuccess: (cambiada) =>
      notificaciones.exito(cambiada.activa ? 'Automatización encendida' : 'Automatización apagada', cambiada.nombre),
    onError: (causa) => notificaciones.error('No se pudo cambiar la automatización', mensajeDeError(causa, ['activa'])),
    onSettled: trasCambiar,
  })

  const eliminacion = useMutation({
    /** @param {Automatizacion} automatizacion */
    mutationFn: (automatizacion) => eliminarAutomatizacion(automatizacion.id),
    onSuccess: (_, automatizacion) => notificaciones.exito('Automatización eliminada', automatizacion.nombre),
    onError: (causa) => notificaciones.error('No se pudo eliminar la automatización', mensajeDeError(causa)),
    onSettled: trasCambiar,
  })

  return {
    lista,
    /** @param {Automatizacion} automatizacion */
    alternar: (automatizacion) => estado.mutate(automatizacion),
    /** @param {Automatizacion} automatizacion */
    eliminar: (automatizacion) => eliminacion.mutateAsync(automatizacion),
  }
}
