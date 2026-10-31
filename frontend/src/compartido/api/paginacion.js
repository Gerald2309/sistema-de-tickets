/**
 * @template T
 * @typedef {{ elementos: T[], pagina: number, ultimaPagina: number, porPagina: number, total: number }} Pagina
 */

/**
 * Convierte una colección paginada de Laravel (`data` + `meta`) en una Pagina del cliente.
 * @template T
 * @param {any} cuerpo respuesta de la API
 * @param {(elemento: any) => T} aElemento adaptador de cada elemento
 * @returns {Pagina<T>}
 */
export function aPagina(cuerpo, aElemento) {
  const meta = cuerpo.meta ?? {}
  return {
    elementos: (cuerpo.data ?? []).map(aElemento),
    pagina: meta.current_page ?? 1,
    ultimaPagina: meta.last_page ?? 1,
    porPagina: meta.per_page ?? cuerpo.data?.length ?? 0,
    total: meta.total ?? cuerpo.data?.length ?? 0,
  }
}

/** @typedef {{ id: number, nombre: string }} Referencia persona, rol o empresa abreviados */

/**
 * La API nombra a las personas con `name` y a roles y empresas con `nombre`; el cliente, siempre `nombre`.
 * @param {{ id: number, name?: string, nombre?: string } | null | undefined} referencia
 * @returns {Referencia | null}
 */
export function aReferencia(referencia) {
  if (!referencia) {
    return null
  }
  return { id: referencia.id, nombre: referencia.name ?? referencia.nombre ?? '' }
}
