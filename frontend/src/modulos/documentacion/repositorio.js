/** Enlaces al código del repositorio público, para pasar de la explicación al archivo real. */

const REPOSITORIO = 'https://github.com/Gerald2309/sistema-de-tickets/blob/main'

/**
 * @param {string} archivo ruta desde la raíz del repositorio; lo que siga a « · » es un comentario
 * @returns {string | null} la dirección del archivo en GitHub, o null si no es una ruta de archivo
 */
export function enlaceAlCodigo(archivo) {
  const ruta = archivo.split(' · ')[0].trim()
  return /^[\w.-]+(\/[\w.@-]+)*\.\w+$/.test(ruta) ? `${REPOSITORIO}/${ruta}` : null
}
