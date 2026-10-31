/**
 * Cada compilación cambia el nombre de los archivos de cada pantalla. Una pestaña abierta antes de publicar una
 * versión nueva pide, al navegar, un archivo que ya no existe: la pantalla no abre. La salida es recargar hacia
 * la ruta pedida, que trae la versión nueva. Una sola vez por ruta: si vuelve a fallar, el problema es otro y
 * no se entra en un ciclo de recargas.
 */

const CLAVE = 'mesa-de-ayuda.recarga-por-version'

// Cómo lo dice cada navegador (Chromium, Firefox, Safari) y Vite cuando falla la hoja de estilos de la pantalla.
const ARCHIVO_QUE_YA_NO_EXISTE =
  /dynamically imported module|Importing a module script failed|Unable to preload CSS|Loading chunk/i

/**
 * @param {unknown} error
 * @returns {boolean} falló la carga de una pantalla porque su archivo es de una versión anterior
 */
export function esDeUnaVersionAnterior(error) {
  const mensaje = error instanceof Error ? error.message : String(error)
  return ARCHIVO_QUE_YA_NO_EXISTE.test(mensaje)
}

/**
 * Recarga hacia la ruta, salvo que ya se haya intentado con ella.
 * @param {string} ruta
 * @param {{ getItem: (clave: string) => string | null, setItem: (clave: string, valor: string) => void }} [almacen]
 * @param {{ assign: (url: string) => void }} [ubicacion]
 * @returns {boolean} se recargó
 */
export function recargarHacia(ruta, almacen = sessionStorage, ubicacion = window.location) {
  try {
    if (almacen.getItem(CLAVE) === ruta) {
      return false
    }
    almacen.setItem(CLAVE, ruta)
  } catch {
    // Sin almacenamiento no se puede evitar el ciclo: mejor no recargar.
    return false
  }
  ubicacion.assign(ruta)
  return true
}

/**
 * Tras una navegación que sí funcionó, una versión futura vuelve a poder recargar.
 * @param {{ removeItem: (clave: string) => void }} [almacen]
 * @returns {void}
 */
export function olvidarRecarga(almacen = sessionStorage) {
  try {
    almacen.removeItem(CLAVE)
  } catch {
    // Sin almacenamiento no hay nada que olvidar.
  }
}
