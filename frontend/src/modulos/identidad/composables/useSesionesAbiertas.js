import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { clavesDeIdentidad } from '../api/claves.js'
import { cerrarLasDemasSesiones, cerrarOtraSesion, listarSesiones } from '../api/sesion.js'

/** Las sesiones abiertas de la persona, para cerrar las que no reconozca. */
export function useSesionesAbiertas() {
  const queryClient = useQueryClient()
  const notificaciones = useNotificaciones()

  const lista = useQuery({
    queryKey: clavesDeIdentidad.sesiones(),
    queryFn: ({ signal }) => listarSesiones(signal),
  })
  const hayOtras = computed(() => (lista.data.value ?? []).some((sesion) => !sesion.actual))

  const alCerrar = (/** @type {string} */ titulo) => ({
    onSuccess: async () => {
      notificaciones.exito(titulo, 'Ese equipo tendrá que volver a iniciar sesión.')
      await queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.sesiones() })
    },
    onError: (/** @type {unknown} */ causa) => notificaciones.error('No se pudo cerrar', mensajeDeError(causa)),
  })

  const cerrarUna = useMutation({ mutationFn: cerrarOtraSesion, ...alCerrar('Sesión cerrada') })
  const cerrarLasDemas = useMutation({ mutationFn: cerrarLasDemasSesiones, ...alCerrar('Sesiones cerradas') })

  return { lista, hayOtras, cerrarUna, cerrarLasDemas }
}
