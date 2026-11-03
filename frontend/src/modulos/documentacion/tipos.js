/**
 * Forma del contenido de la documentación técnica. Las páginas son datos (contenido/*.js) y los componentes
 * solo los dibujan: así el buscador indexa exactamente lo que se muestra.
 *
 * En los textos, lo que va entre comillas invertidas se muestra como código: «el índice `(disparador, activa)`».
 */

/**
 * @typedef {object} Nodo una caja de un plano
 * @property {string} id
 * @property {number} x
 * @property {number} y
 * @property {number} w
 * @property {number} h
 * @property {string} titulo
 * @property {string} [tipo] rótulo pequeño sobre el título: archivo, capa o contenedor
 * @property {string} [sub]
 * @property {boolean} [zona] un área punteada que agrupa cajas (un carril, una capa)
 * @property {boolean} [clave] la pieza central del plano, con borde de acento
 */

/**
 * @typedef {object} Arista una flecha entre dos cajas, en ángulo recto
 * @property {string} de
 * @property {string} a
 * @property {string} [texto]
 * @property {boolean} [punteada] lo que ocurre después, en la cola o tras el commit
 * @property {'h'} [modo] sale de costado y gira en la columna `mx`, para esquivar cajas
 * @property {number} [mx]
 */

/**
 * @typedef {object} Plano un dibujo técnico con cuadrícula y cajetín
 * @property {string} codigo como «A-02»
 * @property {string} titulo
 * @property {string} nivel nivel del modelo C4
 * @property {number} ancho
 * @property {number} alto
 * @property {Nodo[]} nodos
 * @property {Arista[]} [aristas]
 */

/**
 * @typedef {object} Paso un paso de un recorrido: qué cajas y flechas se iluminan y qué ocurre ahí
 * @property {string} titulo
 * @property {'front' | 'back' | 'datos' | 'infra'} capa
 * @property {string[]} nodos
 * @property {string[]} aristas como «origen>destino»
 * @property {string} archivo
 * @property {string} texto
 * @property {string} [codigo]
 */

/**
 * @typedef {object} Foco una lectura de un mismo plano: qué se ilumina y qué explica
 * @property {string} etiqueta
 * @property {string[]} nodos
 * @property {string[]} aristas
 * @property {string} nota
 */

/**
 * @typedef {{ tipo: 'filas', filas: [string, string][] }
 *   | { tipo: 'codigo', codigo: string }
 *   | { tipo: 'nota', texto: string }
 *   | { tipo: 'imagen', src: string, alt: string }
 *   | { tipo: 'plano', plano: Plano }
 *   | { tipo: 'planos', vistas: { etiqueta: string, plano: Plano, nota: string }[] }
 *   | { tipo: 'focos', plano: Plano, focos: Foco[] }
 *   | { tipo: 'recorrido', plano: Plano, pasos: Paso[] }
 *   | { tipo: 'simulador' }
 *   | { tipo: 'permisos' }
 *   | { tipo: 'api' }} Bloque
 */

/**
 * @typedef {object} Seccion una tarjeta de la página
 * @property {string} titulo
 * @property {string} [extra] dato a la derecha de la cabecera: el código del plano o el archivo de la prueba
 * @property {Bloque[]} bloques
 */

/**
 * @typedef {object} Pagina
 * @property {string} id el segmento de la dirección: /documentacion/{id}
 * @property {string} titulo
 * @property {string} [enMenu] un nombre más corto para el menú
 * @property {'inicio' | 'aplicacion' | 'administracion' | 'tecnico'} grupo
 * @property {string} icono
 * @property {string} entradilla
 * @property {Seccion[]} secciones
 */

export {}
