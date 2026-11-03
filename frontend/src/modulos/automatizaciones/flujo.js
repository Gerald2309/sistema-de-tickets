/**
 * Cambios del árbol de pasos. Una lista de pasos termina, si acaso, en una condición con sus caminos Sí y No
 * (la misma forma que valida el backend en LectorDeFlujo).
 */

/** @typedef {import('./tipos.js').Paso} Paso */

/** Como en el backend: más condiciones seguidas vuelven el árbol imposible de leer. */
export const MAXIMO_DE_CONDICIONES_SEGUIDAS = 3

/**
 * Pone el paso en la posición `k` de la lista. Una condición nueva se lleva los pasos que venían después a su
 * camino Sí: nada se pierde.
 * @param {Paso[]} lista
 * @param {number} k
 * @param {Paso} paso
 * @returns {void}
 */
export function insertar(lista, k, paso) {
  if (paso.tipo === 'condicion') {
    paso.si = lista.splice(k)
    lista.push(paso)
  } else {
    lista.splice(k, 0, paso)
  }
}

/**
 * Quita el paso `i`. Si es una condición, su camino Sí ocupa su lugar y el No se descarta.
 * @param {Paso[]} lista
 * @param {number} i
 * @returns {void}
 */
export function quitar(lista, i) {
  const [quitado] = lista.splice(i, 1)
  if (quitado?.tipo === 'condicion') {
    lista.splice(i, 0, ...quitado.si)
  }
}

/**
 * Si los pasos hacen algo en alguno de sus caminos (sin acciones no se puede activar).
 * @param {Paso[]} pasos
 * @returns {boolean}
 */
export function tieneAcciones(pasos) {
  return pasos.some((paso) => paso.tipo === 'accion' || tieneAcciones(paso.si) || tieneAcciones(paso.no))
}

/**
 * La ruta de un paso en el documento (`pasos.0.si.1`), o null si no está.
 * @param {Paso[]} pasos
 * @param {Paso} buscado
 * @param {string} [ruta]
 * @returns {string | null}
 */
export function rutaDe(pasos, buscado, ruta = 'pasos') {
  for (const [i, paso] of pasos.entries()) {
    if (paso === buscado) {
      return `${ruta}.${i}`
    }
    if (paso.tipo === 'condicion') {
      const encontrada = rutaDe(paso.si, buscado, `${ruta}.${i}.si`) ?? rutaDe(paso.no, buscado, `${ruta}.${i}.no`)
      if (encontrada) {
        return encontrada
      }
    }
  }
  return null
}

/**
 * Las personas que nombran los pasos (a quién asignan o avisan), para mostrar su nombre.
 * @param {Paso[]} pasos
 * @returns {number[]}
 */
export function personasNombradas(pasos) {
  return pasos.flatMap((paso) => {
    if (paso.tipo === 'condicion') {
      return [...personasNombradas(paso.si), ...personasNombradas(paso.no)]
    }
    const id = paso.accion === 'asignar' ? paso.usuario_id : paso.destinatario === 'usuario' ? paso.id : null
    return id ? [id] : []
  })
}

/**
 * Los mensajes de la API para un paso: los de sus datos (`pasos.0.usuario_id`) y los suyos, no los de los pasos
 * de sus caminos (`pasos.0.si.1…`), que se muestran en su propio bloque.
 * @param {Record<string, string[]>} errores
 * @param {string} ruta del paso, por ejemplo 'pasos.0.si.1'
 * @returns {string[]}
 */
export function erroresDelPaso(errores, ruta) {
  return Object.entries(errores)
    .filter(([clave]) => {
      const resto = clave.slice(ruta.length)
      return clave.startsWith(ruta) && (resto === '' || (resto.startsWith('.') && !/^\.(si|no)\./.test(resto)))
    })
    .flatMap(([, mensajes]) => mensajes)
}
