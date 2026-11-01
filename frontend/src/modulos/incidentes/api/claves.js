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
  /** @param {number} id */
  mensajes: (id) => ['incidentes', 'detalle', id, 'mensajes'],
  // Fuera de 'incidentes': una imagen enviada no cambia, así que un cambio del incidente no la vuelve a pedir.
  /**
   * @param {number} id
   * @param {number} mensajeId
   * @param {number} adjuntoId
   */
  adjunto: (id, mensajeId, adjuntoId) => ['adjuntos', id, mensajeId, adjuntoId],
  // «Mi día»: dentro de 'incidentes', así un cambio de un incidente refresca también la agenda y la bandeja.
  miDia: ['incidentes', 'mi-dia'],
  /**
   * @param {string} desde
   * @param {string} hasta
   */
  plan: (desde, hasta) => ['incidentes', 'mi-dia', 'plan', desde, hasta],
  /** @param {string} buscar */
  porPlanear: (buscar) => ['incidentes', 'mi-dia', 'por-planear', buscar],
  /** @param {string} hoy */
  pendientes: (hoy) => ['incidentes', 'mi-dia', 'pendientes', hoy],
  /** @param {string} hoy */
  notas: (hoy) => ['incidentes', 'mi-dia', 'notas', hoy],
  ultimoTrabajado: ['incidentes', 'mi-dia', 'ultimo'],
  /** @param {string} prioridad */
  plazoPropuesto: (prioridad) => ['plazo-propuesto', prioridad],
  panel: ['panel'],
  // Dentro de 'panel': «Actualizar» y el tiempo real lo refrescan junto con el resumen.
  indicadores: ['panel', 'indicadores'],
  avisos: ['avisos'],
  catalogos: ['catalogos'],
})
