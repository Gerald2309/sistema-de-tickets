/** Claves de consulta de automatizaciones: jerárquicas, para invalidar por prefijo. */
export const clavesDeAutomatizaciones = Object.freeze({
  todas: () => ['automatizaciones'],
  /** @param {number} id */
  una: (id) => ['automatizaciones', id],
})
