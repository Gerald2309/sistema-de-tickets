/**
 * Tipos del módulo de incidentes (modelo-datos-cliente.md §3.2). Fechas en texto ISO 8601 UTC.
 * @typedef {import('@/compartido/api/paginacion.js').Referencia} Referencia
 */

/**
 * @typedef {object} Incidente
 * @property {number} id
 * @property {string} codigo INC-000605
 * @property {string} titulo
 * @property {string} descripcion
 * @property {string} categoria
 * @property {string} prioridad
 * @property {string} estado
 * @property {string[]} estadosSiguientes los que permite la API desde el estado actual
 * @property {string | null} solucion
 * @property {boolean} vencido
 * @property {string} fechaVencimiento
 * @property {Referencia} creador
 * @property {Referencia | null} asignado
 * @property {Referencia | null} cliente empresa del solicitante al reportar
 * @property {string} creadoEn
 * @property {string} actualizadoEn
 */

/**
 * @typedef {object} ResumenPanel
 * @property {number} total
 * @property {Record<string, number>} porEstado
 * @property {Record<string, number>} porPrioridad
 * @property {number} vencidos
 * @property {Record<string, number>} vencidosPorEstado
 */

/**
 * @typedef {object} AvisoMasivo posible incidente masivo (API 26)
 * @property {string} categoria
 * @property {number} incidentes
 * @property {number} personas
 * @property {string[]} palabras
 * @property {string} desde
 */

/**
 * @typedef {object} EntradaHistorial
 * @property {number} id
 * @property {'creacion' | 'modificacion' | 'borrado'} evento
 * @property {string | null} campo
 * @property {string | null} valorAnterior
 * @property {string | null} valorNuevo
 * @property {Referencia} autor
 * @property {string} creadoEn
 */

/**
 * @typedef {object} Similar caso resuelto parecido (API 24)
 * @property {number} id
 * @property {string} codigo
 * @property {string} titulo
 * @property {string} categoria
 * @property {string} estado
 * @property {string | null} solucion
 */

/**
 * @typedef {object} Catalogos
 * @property {{ valor: string, siguientes: string[] }[]} estados
 * @property {string[]} prioridades
 * @property {string[]} categorias
 */

/**
 * Filtros del listado tal como viven en la dirección (modelo-datos-cliente §5.1). Todo opcional.
 * @typedef {object} FiltrosDeIncidentes
 * @property {'todos' | 'para-mi' | 'vencidos'} [vista]
 * @property {string} [q]
 * @property {string} [estado]
 * @property {string} [prioridad]
 * @property {string} [categoria]
 * @property {number} [asignado]
 * @property {number} [creador]
 * @property {number} [cliente]
 * @property {boolean} [mios] solo para el panel («creados por mí»)
 * @property {number} [vence] horas: pendientes que vencen en ese plazo
 * @property {string} [desde] AAAA-MM-DD local
 * @property {string} [hasta] AAAA-MM-DD local
 * @property {'vencimiento' | 'creacion'} [sobre]
 * @property {'id' | 'prioridad' | 'estado' | 'fecha_vencimiento' | 'creado'} [orden]
 * @property {'asc' | 'desc'} [dir]
 * @property {number} [pagina]
 * @property {number} [por]
 */

export {}
