/**
 * Fábrica de claves de consulta de identidad (modelo-datos-cliente §4.1): jerárquicas, para invalidar por
 * prefijo. Nadie escribe una clave a mano.
 */
export const clavesDeIdentidad = Object.freeze({
  personas: () => ['personas'],
  /** @param {object} criterio */
  busquedaDePersonas: (criterio) => ['personas', criterio],
  clientes: () => ['clientes'],
  /** @param {object} criterio */
  listaDeClientes: (criterio) => ['clientes', criterio],
  usuarios: () => ['usuarios'],
  /** @param {object} filtros */
  listaDeUsuarios: (filtros) => ['usuarios', filtros],
  /** @param {number} id */
  incidentesAbiertos: (id) => ['usuarios', id, 'incidentes-abiertos'],
  roles: () => ['roles'],
  permisos: () => ['permisos'],
})
