/**
 * Tipos del módulo de incidentes (modelo-datos-cliente.md §3.2). Fechas en texto ISO 8601 UTC.
 * @typedef {import('@/compartido/api/paginacion.js').Referencia} Referencia
 */

/**
 * @typedef {object} Incidente
 * @property {number} id
 * @property {string} codigo INC-000605
 * @property {string} titulo
 * @property {string} descripcion vacía en el listado: solo el detalle trae los textos largos
 * @property {string} categoria
 * @property {string} prioridad
 * @property {string} estado
 * @property {string[]} estadosSiguientes los que permite la API desde el estado actual
 * @property {string | null} solucion null también en el listado
 * @property {number | null} calificacion de 1 a 5, la que dio quien lo reportó al confirmar la solución
 * @property {number} mensajesSinLeer de la conversación, para quien pregunta (0 si la respuesta no lo trae)
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
 * @typedef {object} IndicadoresPanel lo que mira hacia atrás (API 11b); tiempos en segundos, null sin datos
 * @property {{ hastaDias: number | null, cantidad: number }[]} espera pendientes por tramo, del más reciente al más antiguo
 * @property {{ fecha: string, entraron: number, resueltos: number }[]} tendencia un día local por entrada
 * @property {ResolucionPanel} resolucion
 */

/**
 * @typedef {object} ResolucionPanel
 * @property {number} dias periodo medido
 * @property {number | null} mediana
 * @property {number | null} medianaAnterior la del periodo anterior, para comparar
 * @property {number | null} aTiempo de 0 a 1
 * @property {Record<string, { mediana: number | null, aTiempo: number | null, cantidad: number }>} porPrioridad
 * @property {number | null} hastaAtender desde que se reporta hasta que alguien empieza a atenderlo
 * @property {number | null} devueltos parte de las soluciones que el solicitante devolvió, de 0 a 1
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
 * @typedef {object} Adjunto imagen de un mensaje
 * @property {number} id
 * @property {string} nombre nombre del archivo en el equipo de quien la envió
 * @property {string} tipo
 * @property {number} bytes
 */

/**
 * @typedef {object} Mensaje mensaje de la conversación de un incidente
 * @property {number} id
 * @property {string | null} cuerpo falta si el mensaje es solo imágenes
 * @property {boolean} interno nota interna: solo la ve soporte
 * @property {Referencia} autor
 * @property {Adjunto[]} adjuntos
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
