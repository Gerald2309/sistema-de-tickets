/**
 * Fábrica de claves de consulta de incidentes (modelo-datos-cliente §4.1): jerárquicas, para invalidar por
 * prefijo. Nadie escribe una clave a mano.
 */
export const claves = Object.freeze({
  todo: ['incidentes'],
  listas: () => ['incidentes', 'lista'],
  /** @param {import('../tipos.js').FiltrosDeIncidentes} filtros */
  lista: (filtros) => ['incidentes', 'lista', filtros],
  conteos: () => ['incidentes', 'conteos'],
  /** @param {import('../tipos.js').FiltrosDeIncidentes} filtros */
  conteo: (filtros) => ['incidentes', 'conteos', filtros],
  /** @param {number} id */
  detalle: (id) => ['incidentes', 'detalle', id],
  /** @param {number} id */
  historial: (id) => ['incidentes', 'detalle', id, 'historial'],
  /** @param {number} id */
  similares: (id) => ['incidentes', 'detalle', id, 'similares'],
  panel: ['panel'],
  avisos: ['avisos'],
  catalogos: ['catalogos'],
})
