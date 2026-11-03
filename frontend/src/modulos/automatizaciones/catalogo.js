import { iconos } from '@/compartido/iconos.js'
import { CATEGORIAS, PRIORIDADES } from '@/modulos/incidentes/index.js'

/**
 * Lo que se puede armar en una automatización y cómo se lee cada pieza: los mismos valores que acepta la API
 * (Disparador, CampoDeCondicion y TipoDeAccion en el backend).
 */

/** @typedef {import('./tipos.js').Paso} Paso */
/** @typedef {import('./tipos.js').Disparo} Disparo */
/** @typedef {import('./tipos.js').Nombres} Nombres */

/** @type {Readonly<Record<string, string>>} */
export const PLAZOS = Object.freeze({ respuesta: 'respuesta', resolucion: 'resolución' })

/** @type {Readonly<Record<string, { nombre: string, medida: 'espera' | 'porcentaje' | 'plazo' | null }>>} */
export const DISPARADORES = Object.freeze({
  creacion: { nombre: 'Se crea un incidente', medida: null },
  sla_por_vencer: { nombre: 'El SLA está por vencer', medida: 'porcentaje' },
  sla_vencido: { nombre: 'El SLA se venció', medida: 'plazo' },
  sin_respuesta: { nombre: 'Lleva un tiempo sin respuesta', medida: 'espera' },
  resuelto_sin_confirmar: { nombre: 'Lleva un tiempo resuelto sin confirmar', medida: 'espera' },
})

/** @type {Readonly<Record<string, Partial<Disparo>>>} medida con la que empieza cada disparador al elegirlo */
const MEDIDA_INICIAL = Object.freeze({
  sla_por_vencer: { porcentaje: 80, plazo: 'respuesta' },
  sla_vencido: { plazo: 'resolucion' },
  sin_respuesta: { espera_minutos: 4 * 60 },
  resuelto_sin_confirmar: { espera_minutos: 3 * 24 * 60 },
})

/**
 * @param {string} disparador
 * @returns {Disparo}
 */
export function disparoInicial(disparador) {
  return { disparador, espera_minutos: null, porcentaje: null, plazo: null, ...MEDIDA_INICIAL[disparador] }
}

/** Unidades de una espera, de la mayor a la menor. */
export const UNIDADES = Object.freeze({
  dias: { minutos: 24 * 60, singular: 'día', plural: 'días' },
  horas: { minutos: 60, singular: 'hora', plural: 'horas' },
  minutos: { minutos: 1, singular: 'minuto', plural: 'minutos' },
})

/**
 * La espera en la unidad más grande que la expresa exacta: 4320 → 3 días.
 * @param {number} minutos
 * @returns {{ cantidad: number, unidad: keyof typeof UNIDADES }}
 */
export function aEspera(minutos) {
  const claves = /** @type {Array<keyof typeof UNIDADES>} */ (Object.keys(UNIDADES))
  const unidad = claves.find((clave) => minutos % UNIDADES[clave].minutos === 0) ?? 'minutos'
  return { cantidad: minutos / UNIDADES[unidad].minutos, unidad }
}

/**
 * @param {number} minutos
 * @returns {string} «4 horas», «1 día»
 */
export function duracion(minutos) {
  const { cantidad, unidad } = aEspera(minutos)
  return `${cantidad} ${cantidad === 1 ? UNIDADES[unidad].singular : UNIDADES[unidad].plural}`
}

/**
 * @param {Disparo} disparo
 * @returns {string} la medida en palabras, debajo del nombre del disparador
 */
export function describirDisparo(disparo) {
  const plazo = PLAZOS[disparo.plazo ?? ''] ?? '…'
  switch (disparo.disparador) {
    case 'sla_por_vencer':
      return `Al ${disparo.porcentaje} % del plazo de ${plazo} o más`
    case 'sla_vencido':
      return `Venció el plazo de ${plazo}`
    case 'sin_respuesta':
      return `Sin respuesta de soporte por ${duracion(disparo.espera_minutos ?? 0)}`
    case 'resuelto_sin_confirmar':
      return `Resuelto y sin confirmar por ${duracion(disparo.espera_minutos ?? 0)}`
    default:
      return 'Desde la aplicación o la API'
  }
}

/** @type {Readonly<Record<string, string>>} nombres de las prioridades en minúscula, para leerlas en una frase */
const PRIORIDAD_EN_FRASE = Object.freeze(
  Object.fromEntries(Object.entries(PRIORIDADES).map(([valor, { nombre }]) => [valor, nombre.toLowerCase()])),
)

/**
 * Bloques «Si». `opciones`: los valores fijos que se eligen de una lista (empresa la llena la API).
 * @type {Readonly<Record<string, { titulo: string, descripcion: string, icono: string, sujeto: string, opciones?: Readonly<Record<string, string>> }>>}
 */
export const CONDICIONES = Object.freeze({
  empresa: { titulo: 'Empresa', descripcion: 'Solo de ciertas empresas', icono: iconos.empresa, sujeto: 'La empresa' },
  categoria: {
    titulo: 'Categoría',
    descripcion: 'Red, correo, accesos…',
    icono: iconos.categoria,
    sujeto: 'La categoría',
    opciones: CATEGORIAS,
  },
  prioridad: {
    titulo: 'Prioridad',
    descripcion: 'Baja, media, alta o crítica',
    icono: iconos.prioridad,
    sujeto: 'La prioridad',
    opciones: PRIORIDAD_EN_FRASE,
  },
  palabras: {
    titulo: 'Contiene palabras',
    descripcion: 'En el título o la descripción',
    icono: iconos.palabras,
    sujeto: 'El texto',
  },
  plazo_respuesta_consumido: {
    titulo: 'Plazo de respuesta consumido',
    descripcion: 'Qué parte del plazo para responder ya pasó',
    icono: iconos.plazo,
    sujeto: 'El plazo de respuesta consumido',
  },
  plazo_resolucion_consumido: {
    titulo: 'Plazo de resolución consumido',
    descripcion: 'Qué parte del plazo para resolver ya pasó',
    icono: iconos.vencido,
    sujeto: 'El plazo de resolución consumido',
  },
})

/** @param {string} campo */
const esPlazo = (campo) => campo.startsWith('plazo_')

/**
 * Si el operador compara con un solo valor en orden (≥, ≤, =) en lugar de con una lista.
 * @param {string} operador
 * @returns {boolean}
 */
export const comparaConUnValor = (operador) => ['al_menos', 'como_maximo', 'igual'].includes(operador)

/**
 * Las comparaciones que admite cada campo, como se leen en el panel (CampoDeCondicion::operadores en el backend):
 * con orden (prioridad, plazos) se puede comparar con un valor.
 * @param {string} campo
 * @returns {Array<{ valor: string, nombre: string }>}
 */
export function operadoresDe(campo) {
  if (esPlazo(campo)) {
    return [
      { valor: 'al_menos', nombre: 'Es al menos (≥)' },
      { valor: 'como_maximo', nombre: 'Es como máximo (≤)' },
      { valor: 'igual', nombre: 'Es igual a (=)' },
    ]
  }
  const lista =
    campo === 'palabras'
      ? [
          { valor: 'es_una_de', nombre: 'Menciona alguna de' },
          { valor: 'no_es_ninguna_de', nombre: 'No menciona ninguna de' },
        ]
      : [
          { valor: 'es_una_de', nombre: 'Es una de' },
          { valor: 'no_es_ninguna_de', nombre: 'No es ninguna de' },
        ]
  return campo === 'prioridad'
    ? [
        ...lista,
        { valor: 'al_menos', nombre: 'Es al menos (≥)' },
        { valor: 'como_maximo', nombre: 'Es como máximo (≤)' },
      ]
    : lista
}

/**
 * El valor con el que empieza una comparación: lo más común para cada campo.
 * @param {string} campo
 * @returns {number | string}
 */
export const valorParaComparar = (campo) => (esPlazo(campo) ? 80 : 'alta')

/** @type {Readonly<Record<string, string>>} cómo se lee cada comparación en una frase */
const COMPARACION_EN_FRASE = Object.freeze({ al_menos: 'es al menos', como_maximo: 'es como máximo', igual: 'es' })

const CIERRE =
  'Cerramos tu caso {número} porque no recibimos confirmación. Si el problema sigue, crea una nueva solicitud.'

/**
 * Bloques «Entonces», con los datos con los que empieza cada uno.
 * @type {Readonly<Record<string, { titulo: string, descripcion: string, icono: string, datos: Record<string, unknown> }>>}
 */
export const ACCIONES = Object.freeze({
  asignar: {
    titulo: 'Asignar',
    descripcion: 'A una persona de soporte',
    icono: iconos.reasignar,
    datos: { usuario_id: null },
  },
  responder: {
    titulo: 'Responder al solicitante',
    descripcion: 'Un mensaje con sus datos',
    icono: iconos.responder,
    datos: { mensaje: 'Hola {nombre}, recibimos tu caso {número}. Te escribimos apenas tengamos novedades.' },
  },
  avisar: {
    titulo: 'Avisar',
    descripcion: 'A una persona o un rol, en la campanita',
    icono: iconos.avisos,
    datos: { destinatario: 'rol', id: null },
  },
  cambiar_prioridad: {
    titulo: 'Cambiar prioridad',
    descripcion: 'Subirla o bajarla',
    icono: iconos.prioridad,
    datos: { prioridad: 'alta' },
  },
  cambiar_categoria: {
    titulo: 'Cambiar categoría',
    descripcion: 'Reclasificar el incidente',
    icono: iconos.categoria,
    datos: { categoria: 'red' },
  },
  nota_interna: {
    titulo: 'Nota interna',
    descripcion: 'Un aviso que solo ve soporte',
    icono: iconos.notaInterna,
    datos: { texto: '' },
  },
  cerrar: {
    titulo: 'Cerrar',
    descripcion: 'Solo si está resuelto; con un mensaje al solicitante',
    icono: iconos.cerrarIncidente,
    datos: { mensaje: CIERRE },
  },
})

/** Las variables que admite un mensaje (PlantillaDeMensaje en el backend). */
export const VARIABLES = Object.freeze(['nombre', 'número', 'título', 'empresa', 'prioridad'])

/** @type {Readonly<Record<string, string>>} un incidente de ejemplo, para la vista previa de los mensajes */
const MUESTRA = Object.freeze({
  nombre: 'Marta Ruiz',
  número: 'INC-001024',
  título: 'Sin internet en la sede norte',
  empresa: 'Transportes Sabana',
  prioridad: 'alta',
})

/**
 * El mensaje como lo leería el solicitante del incidente de ejemplo.
 * @param {string} plantilla
 * @returns {string}
 */
export function vistaPrevia(plantilla) {
  return plantilla.replace(/\{([^{}]*)\}/g, (variable, nombre) => MUESTRA[nombre] ?? variable)
}

/** Qué hace cada tipo de paso, para la ayuda del panel. */
export const AYUDA = Object.freeze({
  cuando: 'Lo que pone en marcha el flujo. Los que dependen del tiempo se revisan cada cinco minutos.',
  si: 'Si se cumple, el incidente sigue por el camino Sí; si no, por el camino No. Cada camino tiene sus propios pasos.',
  entonces:
    'Las acciones se hacen en orden y quedan en el historial del incidente a nombre de quien guardó la automatización. Si una no se puede hacer, la automatización se detiene ahí.',
})

/**
 * Un paso nuevo con sus datos iniciales.
 * @param {'condicion' | 'accion'} tipo
 * @param {string} clave el campo de la condición o el tipo de acción
 * @returns {Paso}
 */
export function nuevoPaso(tipo, clave) {
  if (tipo === 'accion') {
    return { tipo, accion: clave, ...structuredClone(ACCIONES[clave].datos) }
  }
  // Un plazo consumido solo se compara («al menos el 80 %»); lo demás empieza como una lista vacía.
  return esPlazo(clave)
    ? {
        tipo,
        campo: /** @type {any} */ (clave),
        operador: 'al_menos',
        valores: [valorParaComparar(clave)],
        si: [],
        no: [],
      }
    : { tipo, campo: /** @type {any} */ (clave), operador: 'es_una_de', valores: [], si: [], no: [] }
}

/**
 * @param {Paso} paso
 * @returns {{ titulo: string, icono: string }}
 */
export function bloqueDe(paso) {
  return paso.tipo === 'condicion' ? CONDICIONES[paso.campo] : ACCIONES[paso.accion]
}

/**
 * @param {string} texto
 * @param {number} [maximo]
 * @returns {string}
 */
const recortar = (texto, maximo = 60) => (texto.length > maximo ? `${texto.slice(0, maximo).trimEnd()}…` : texto)

/**
 * El paso en una línea, con los nombres de lo elegido: «La empresa no es Banco Aliado ni Clínica del Norte».
 * @param {Paso} paso
 * @param {Nombres} nombres
 * @returns {string}
 */
export function textoDelPaso(paso, nombres) {
  if (paso.tipo === 'condicion') {
    const bloque = CONDICIONES[paso.campo]
    const nombre = (/** @type {number | string} */ valor) =>
      paso.campo === 'empresa'
        ? (nombres.empresa(Number(valor)) ?? '…')
        : paso.campo === 'palabras'
          ? `«${valor}»`
          : esPlazo(paso.campo)
            ? `el ${valor} %`
            : (bloque.opciones?.[valor] ?? String(valor))
    if (comparaConUnValor(paso.operador)) {
      return `${bloque.sujeto} ${COMPARACION_EN_FRASE[paso.operador]} ${paso.valores.length ? nombre(paso.valores[0]) : '…'}`
    }
    const niega = paso.operador === 'no_es_ninguna_de'
    const lista = paso.valores.length ? paso.valores.map(nombre).join(niega ? ' ni ' : ' o ') : '… (elija al menos una)'
    if (paso.campo === 'palabras') {
      return `${niega ? 'No menciona' : 'Menciona'} ${lista}`
    }
    return `${bloque.sujeto} ${niega ? 'no es' : 'es'} ${lista}`
  }
  switch (paso.accion) {
    case 'asignar':
      return `Asignar a ${(paso.usuario_id && nombres.persona(paso.usuario_id)) || '…'}`
    case 'avisar':
      return paso.destinatario === 'rol'
        ? `Avisar al rol ${(paso.id && nombres.rol(paso.id)) || '…'}`
        : `Avisar a ${(paso.id && nombres.persona(paso.id)) || '…'}`
    case 'cambiar_prioridad':
      return `Poner la prioridad en ${PRIORIDAD_EN_FRASE[paso.prioridad ?? ''] ?? '…'}`
    case 'cambiar_categoria':
      return `Pasarlo a la categoría ${CATEGORIAS[paso.categoria ?? ''] ?? '…'}`
    case 'nota_interna':
      return paso.texto ? `«${recortar(paso.texto)}»` : 'Escriba la nota'
    default:
      return paso.mensaje ? `«${recortar(paso.mensaje)}»` : 'Escriba el mensaje'
  }
}

/**
 * @param {string} texto
 * @returns {string} con la primera letra en minúscula (los nombres propios del resto se conservan)
 */
const enMinuscula = (texto) => texto.charAt(0).toLowerCase() + texto.slice(1)

/**
 * Los pasos en una frase: «si A: (si B: x; si no: y); si no: nada». Los caminos con otra condición van entre
 * paréntesis.
 * @param {Paso[]} pasos
 * @param {Nombres} nombres
 * @returns {string}
 */
function frase(pasos, nombres) {
  const camino = (/** @type {Paso[]} */ lista) =>
    lista.some((paso) => paso.tipo === 'condicion') ? `(${frase(lista, nombres)})` : frase(lista, nombres)
  return (
    pasos
      .map((paso) =>
        paso.tipo === 'condicion'
          ? `si ${enMinuscula(textoDelPaso(paso, nombres))}: ${camino(paso.si)}; si no: ${camino(paso.no)}`
          : enMinuscula(textoDelPaso(paso, nombres)),
      )
      .join(', luego ') || 'nada'
  )
}

/**
 * La automatización entera en una frase, para leerla de un vistazo.
 * @param {Disparo} disparo
 * @param {Paso[]} pasos
 * @param {Nombres} nombres
 * @returns {string}
 */
export function fraseDeLaAutomatizacion(disparo, pasos, nombres) {
  const cuando = enMinuscula(DISPARADORES[disparo.disparador]?.nombre ?? disparo.disparador)
  const medida = disparo.disparador === 'creacion' ? '' : ` (${enMinuscula(describirDisparo(disparo))})`
  return `Cuando ${cuando}${medida}: ${frase(pasos, nombres)}.`
}

/**
 * Por dónde salió un grupo de incidentes en una prueba: «Categoría sí → Empresa no».
 * @param {Array<{ campo: string, cumple: boolean }>} decisiones
 * @returns {string}
 */
export function describirCamino(decisiones) {
  return (
    decisiones
      .map(({ campo, cumple }) => `${CONDICIONES[campo]?.titulo ?? campo} ${cumple ? 'sí' : 'no'}`)
      .join(' → ') || 'Sin condiciones'
  )
}
