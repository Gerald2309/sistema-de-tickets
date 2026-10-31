import { computed, ref } from 'vue'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { cambiarContrasena } from '../api/sesion.js'

/** Mínimo de la política de contraseñas del backend (ASVS 6.2.1): solo longitud, sin reglas de composición. */
export const LONGITUD_MINIMA = 15
export const LONGITUD_MAXIMA = 256

/** Cambio de la propia contraseña (RF-F08). Tras el éxito, el servidor cierra las demás sesiones. */
export function useCambioDeContrasena() {
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

  const cumpleLongitud = computed(() => nueva.value.length >= LONGITUD_MINIMA)
  const coincide = computed(() => repetida.value === '' || repetida.value === nueva.value)
  const listo = computed(() => actual.value !== '' && cumpleLongitud.value && repetida.value === nueva.value)

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
    cumpleLongitud,
    coincide,
    listo,
    guardar,
    limpiarCampo,
  }
}
