import { computed } from 'vue'
import { useSesion } from '@/modulos/identidad/index.js'
import { navegacion } from '@/nucleo/enrutador/index.js'

/** Las entradas de navegación que la persona puede usar, para el menú lateral y la paleta de comandos (RF-F06). */
export function useNavegacion() {
  const sesion = useSesion()
  const permitidas = computed(() => navegacion.filter((entrada) => sesion.tieneAlguno(...entrada.permisos)))
  const deTrabajo = computed(() => permitidas.value.filter((entrada) => entrada.grupo === 'trabajo'))
  const deAdministracion = computed(() => permitidas.value.filter((entrada) => entrada.grupo === 'administracion'))
  return { permitidas, deTrabajo, deAdministracion }
}
