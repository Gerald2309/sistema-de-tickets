import { useMutation } from '@tanstack/vue-query'
import { computed } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { confirmarSolucion } from '../api/incidentes.js'
import { useInvalidacion } from './useInvalidacion.js'

/**
 * Quien reportó el incidente dice si la solución sirvió: lo cierra o lo devuelve a soporte. Lo usan el
 * detalle y el inicio del solicitante, con los mismos avisos.
 */
export function useConfirmacionDeSolucion() {
  const notificaciones = useNotificaciones()
  const { trasCambiarIncidentes } = useInvalidacion()

  const confirmacion = useMutation({
    /** @param {{ id: number, respuesta: Parameters<typeof confirmarSolucion>[1] }} pedido */
    mutationFn: ({ id, respuesta }) => confirmarSolucion(id, respuesta),
    onSuccess: async (actualizado, { respuesta }) => {
      if (respuesta.solucionado) {
        notificaciones.exito(`${actualizado.codigo} cerrado`, 'Gracias por confirmarlo.')
      } else {
        notificaciones.info(`${actualizado.codigo} volvió a soporte`, 'Su comentario quedó en la conversación.')
      }
      await trasCambiarIncidentes([actualizado.id])
    },
    onError: (causa) => notificaciones.error('No se guardó su respuesta', mensajeDeError(causa, ['motivo'])),
  })

  /**
   * @param {number} id
   * @param {Parameters<typeof confirmarSolucion>[1]} respuesta
   * @returns {Promise<boolean>} se aplicó
   */
  async function confirmar(id, respuesta) {
    return confirmacion
      .mutateAsync({ id, respuesta })
      .then(() => true)
      .catch(() => false)
  }

  return {
    confirmar,
    confirmando: computed(() => confirmacion.isPending.value),
    // En el inicio hay varias tarjetas: solo la que se está enviando muestra la espera.
    /** @param {number} id */
    confirmandoA: (id) => confirmacion.isPending.value && confirmacion.variables.value?.id === id,
  }
}
