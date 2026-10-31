import { ref } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { exportarIncidentes } from '../api/incidentes.js'

/**
 * Descarga del listado en CSV o Excel con los filtros y el orden activos (RF-F25). Se pide con el token en la
 * cabecera (no con un enlace) y se guarda con un enlace temporal que se revoca al terminar.
 * @param {() => import('../tipos.js').FiltrosDeIncidentes} filtrosActivos
 */
export function useDescarga(filtrosActivos) {
  const notificaciones = useNotificaciones()
  const descargando = ref(false)
  const progreso = ref(/** @type {number | null} */ (null))

  /**
   * @param {'csv' | 'xlsx'} formato
   * @returns {Promise<void>}
   */
  async function descargar(formato) {
    if (descargando.value) {
      return
    }
    descargando.value = true
    progreso.value = null
    try {
      const archivo = await exportarIncidentes(filtrosActivos(), formato, (avance) => (progreso.value = avance))
      const nombre = `incidentes-${new Date().toLocaleDateString('sv-SE')}.${formato}`
      guardar(archivo, nombre)
      notificaciones.exito('Descarga lista', nombre)
    } catch (causa) {
      // Un 422 por exceso de resultados trae el mensaje de la API («acote la búsqueda»).
      notificaciones.error('No se pudo descargar', mensajeDeError(causa))
    } finally {
      descargando.value = false
      progreso.value = null
    }
  }

  return { descargar, descargando, progreso }
}

/**
 * @param {Blob} archivo
 * @param {string} nombre
 * @returns {void}
 */
function guardar(archivo, nombre) {
  const url = URL.createObjectURL(archivo)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = nombre
  document.body.appendChild(enlace)
  enlace.click()
  enlace.remove()
  URL.revokeObjectURL(url)
}
