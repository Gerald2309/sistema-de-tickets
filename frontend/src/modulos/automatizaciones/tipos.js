/**
 * Tipos del módulo de automatizaciones. Los pasos y el disparo viajan con la forma de la API (snake_case): son un
 * documento que el editor cambia y devuelve tal cual, y así los errores de la API (`pasos.0.si.1.usuario_id`)
 * señalan directamente el dato.
 */

/**
 * @typedef {object} Accion un paso «Entonces»; los demás datos dependen de `accion` (ACCIONES en catalogo.js)
 * @property {'accion'} tipo
 * @property {string} accion
 * @property {number | null} [usuario_id]
 * @property {number | null} [id]
 * @property {'rol' | 'usuario'} [destinatario]
 * @property {string} [mensaje]
 * @property {string} [texto]
 * @property {string} [prioridad]
 * @property {string} [categoria]
 */

/**
 * @typedef {object} Condicion un paso «Si»: abre un camino Sí y uno No
 * @property {'condicion'} tipo
 * @property {'empresa' | 'categoria' | 'prioridad' | 'palabras' | 'plazo_respuesta_consumido' | 'plazo_resolucion_consumido'} campo
 * @property {'es_una_de' | 'no_es_ninguna_de' | 'al_menos' | 'como_maximo' | 'igual'} operador ≥, ≤ e = solo
 *   donde hay orden (prioridad, plazos consumidos), y entonces con un solo valor
 * @property {Array<number | string>} valores
 * @property {Paso[]} si
 * @property {Paso[]} no
 */

/** @typedef {Accion | Condicion} Paso */

/**
 * @typedef {object} Disparo el paso «Cuándo»; solo lleva la medida que usa su disparador
 * @property {string} disparador
 * @property {number | null} espera_minutos
 * @property {number | null} porcentaje
 * @property {'respuesta' | 'resolucion' | null} plazo
 */

/**
 * @typedef {object} Automatizacion
 * @property {number} id
 * @property {string} nombre
 * @property {boolean} activa
 * @property {Disparo} disparo
 * @property {Paso[]} pasos
 * @property {import('@/compartido/api/paginacion.js').Referencia} autor lo que hace queda a su nombre
 * @property {number} aplicaciones
 * @property {number} aplicacionesConError
 * @property {string | null} ultimaAplicacionEn
 */

/**
 * @typedef {object} ResultadoDePrueba lo que habrían hecho unos pasos con los incidentes recientes
 * @property {number} dias
 * @property {number} evaluados
 * @property {number} conAcciones
 * @property {Array<{ decisiones: Array<{ campo: string, cumple: boolean }>, acciones: string[], cantidad: number }>} caminos
 * @property {Array<{ id: number, codigo: string, titulo: string }>} ejemplos
 * @property {{ lesToca: number, conAcciones: number } | null} ahora con un disparador por tiempo: a cuántos les llegó el
 *   momento ahora mismo y cuántos de ellos recibirían acciones
 */

/**
 * Nombres de lo que los pasos guardan como id, para mostrarlos.
 * @typedef {object} Nombres
 * @property {(id: number) => string | undefined} empresa
 * @property {(id: number) => string | undefined} persona
 * @property {(id: number) => string | undefined} rol
 */

export {}
