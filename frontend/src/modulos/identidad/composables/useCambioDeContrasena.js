import { computed, ref } from 'vue'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { cambiarContrasena } from '../api/sesion.js'
import { cumpleRequisitos } from '../contrasena.js'

/**
 * Cambio de la propia contraseña (RF-F08). Tras el éxito, el servidor cierra las demás sesiones.
 * @param {{ alCambiar?: () => void }} [opciones]
 */
export function useCambioDeContrasena({ alCambiar } = {}) {
  const notificaciones = useNotificaciones()
  const actual = ref('')
  const nueva = ref('')
  const repetida = ref('')
  const pendiente = ref(false)
  const exito = ref(false)
  const { errores, general, aplicar, limpiar, limpiarCampo } = useErroresDeFormulario({
    password_actual: 'actual',
    password: 'nueva',
  })

  const coincide = computed(() => repetida.value === '' || repetida.value === nueva.value)
  const listo = computed(() => actual.value !== '' && cumpleRequisitos(nueva.value) && repetida.value === nueva.value)

  /** @returns {Promise<void>} */
  async function guardar() {
    if (!listo.value || pendiente.value) {
      return
    }
    limpiar()
    pendiente.value = true
    try {
      await cambiarContrasena(actual.value, nueva.value)
      actual.value = nueva.value = repetida.value = ''
      exito.value = true
      setTimeout(() => (exito.value = false), 1500)
      notificaciones.exito('Contraseña actualizada', 'Se cerraron sus demás sesiones.')
      alCambiar?.()
    } catch (causa) {
      aplicar(causa)
    } finally {
      pendiente.value = false
    }
  }

  return {
    actual,
    nueva,
    repetida,
    pendiente,
    exito,
    errores,
    general,
    coincide,
    listo,
    guardar,
    limpiarCampo,
  }
}
