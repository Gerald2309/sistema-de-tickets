import { aReferencia } from '@/compartido/api/paginacion.js'
import { limiteDelDia } from '@/compartido/presentacion/fechas.js'

/**
 * Adaptadores entre la API y el cliente (patrón Adaptador, modelo-datos-cliente §3.1): lo único que conoce
 * los nombres del servidor. Explícitos campo a campo; lo que la API agregue y aquí no se use, se ignora.
 */

/** @typedef {import('../tipos.js').Incidente} Incidente */
/** @typedef {import('../tipos.js').FiltrosDeIncidentes} FiltrosDeIncidentes */

/** @param {{ id: number, name?: string, nombre?: string } | null | undefined} dto */
const referenciaObligatoria = (dto) => /** @type {import('../tipos.js').Referencia} */ (aReferencia(dto))

/**
 * @param {any} dto `IncidenteResource`
 * @returns {Incidente}
 */
export function aIncidente(dto) {
  return {
    id: dto.id,
    codigo: dto.codigo,
    titulo: dto.titulo,
    // El listado no trae los textos largos: solo el detalle.
    descripcion: dto.descripcion ?? '',
    categoria: dto.categoria,
    prioridad: dto.prioridad,
    estado: dto.estado,
    estadosSiguientes: dto.estados_siguientes ?? [],
    solucion: dto.solucion ?? null,
    calificacion: dto.calificacion ?? null,
    mensajesSinLeer: dto.mensajes_sin_leer ?? 0,
    vencido: dto.vencido === true,
    fechaVencimiento: dto.fecha_vencimiento,
    fechaRequerida: dto.fecha_requerida ?? null,
    venceRespuestaEn: dto.vence_respuesta_en ?? null,
    primeraRespuestaEn: dto.primera_respuesta_en ?? null,
    resueltoEn: dto.resuelto_en ?? null,
    creador: referenciaObligatoria(dto.creador),
    asignado: aReferencia(dto.asignado),
    cliente: aReferencia(dto.cliente),
    creadoEn: dto.created_at,
    actualizadoEn: dto.updated_at,
  }
}

/**
 * @param {any} dto `DashboardResource`
 * @returns {import('../tipos.js').ResumenPanel}
 */
export function aResumenPanel(dto) {
  return {
    total: dto.total,
    porEstado: dto.por_estado,
    porPrioridad: dto.por_prioridad,
    vencidos: dto.vencidos,
    vencidosPorEstado: dto.vencidos_por_estado ?? {},
  }
}

/**
 * @param {any} dto `IndicadoresResource`
 * @returns {import('../tipos.js').IndicadoresPanel}
 */
export function aIndicadoresPanel(dto) {
  const resolucion = dto.resolucion
  return {
    espera: dto.espera.map((/** @type {any} */ tramo) => ({ hastaDias: tramo.hasta_dias, cantidad: tramo.cantidad })),
    tendencia: dto.tendencia,
    resolucion: {
      dias: resolucion.dias,
      mediana: resolucion.mediana_segundos,
      plazo: resolucion.plazo_segundos,
      medianaAnterior: resolucion.mediana_anterior_segundos,
      aTiempo: resolucion.a_tiempo,
      porPrioridad: Object.fromEntries(
        Object.entries(resolucion.por_prioridad).map(([prioridad, /** @type {any} */ datos]) => [
          prioridad,
          {
            mediana: datos.mediana_segundos,
            plazo: datos.plazo_segundos,
            aTiempo: datos.a_tiempo,
            cantidad: datos.cantidad,
          },
        ]),
      ),
      primeraRespuesta: resolucion.primera_respuesta_segundos,
      respuestaATiempo: resolucion.respuesta_a_tiempo,
      devueltos: resolucion.devueltos,
    },
  }
}

/**
 * @param {any} dto `AvisoResource`
 * @returns {import('../tipos.js').AvisoMasivo}
 */
export function aAviso(dto) {
  return {
    categoria: dto.categoria,
    incidentes: dto.incidentes,
    personas: dto.personas,
    palabras: dto.palabras,
    desde: dto.desde,
  }
}

/**
 * @param {any} dto `HistorialResource`
 * @returns {import('../tipos.js').EntradaHistorial}
 */
export function aEntradaHistorial(dto) {
  return {
    id: dto.id,
    evento: dto.evento,
    campo: dto.campo ?? null,
    valorAnterior: dto.valor_anterior ?? null,
    valorNuevo: dto.valor_nuevo ?? null,
    autor: referenciaObligatoria(dto.autor),
    creadoEn: dto.created_at,
  }
}

/**
 * @param {any} dto `MensajeResource`
 * @returns {import('../tipos.js').Mensaje}
 */
export function aMensaje(dto) {
  return {
    id: dto.id,
    cuerpo: dto.cuerpo ?? null,
    interno: Boolean(dto.interno),
    autor: referenciaObligatoria(dto.autor),
    adjuntos: (dto.adjuntos ?? []).map((/** @type {any} */ adjunto) => ({
      id: adjunto.id,
      nombre: adjunto.nombre,
      tipo: adjunto.tipo,
      bytes: adjunto.bytes,
    })),
    creadoEn: dto.created_at,
  }
}

/**
 * @param {any} dto `SimilarResource`
 * @returns {import('../tipos.js').Similar}
 */
export function aSimilar(dto) {
  return {
    id: dto.id,
    codigo: dto.codigo,
    titulo: dto.titulo,
    categoria: dto.categoria,
    estado: dto.estado,
    solucion: dto.solucion,
  }
}

/**
 * @param {any} dto `CatalogosResource`
 * @returns {import('../tipos.js').Catalogos}
 */
export function aCatalogos(dto) {
  return {
    estados: dto.estados.map((/** @type {any} */ estado) => ({ valor: estado.valor, siguientes: estado.siguientes })),
    prioridades: dto.prioridades,
    categorias: dto.categorias,
  }
}

/**
 * Filtros del cliente → parámetros de `GET /incidentes` (ListarIncidentesRequest).
 * @param {FiltrosDeIncidentes} filtros
 * @returns {Record<string, string | number>}
 */
export function aParametrosDeListado(filtros) {
  /** @type {Record<string, string | number>} */
  const parametros = {}
  const poner = (/** @type {string} */ clave, /** @type {string | number | undefined} */ valor) => {
    if (valor !== undefined && valor !== '') {
      parametros[clave] = valor
    }
  }
  if (filtros.vista === 'para-mi') {
    parametros.asignados_a_mi = 1
  } else if (filtros.vista === 'vencidos') {
    parametros.vencidos = 1
  }
  if (filtros.mios) {
    parametros.mios = 1
  }
  poner('q', filtros.q?.trim())
  poner('estado', filtros.estado)
  poner('prioridad', filtros.prioridad)
  poner('categoria', filtros.categoria)
  poner('usuario_asignado_id', filtros.asignado)
  poner('usuario_creador_id', filtros.creador)
  poner('cliente_id', filtros.cliente)
  poner('vence_en_horas', filtros.vence)
  if (filtros.desde) {
    parametros.fecha_desde = limiteDelDia(filtros.desde, 'inicio')
  }
  if (filtros.hasta) {
    parametros.fecha_hasta = limiteDelDia(filtros.hasta, 'fin')
  }
  if ((filtros.desde || filtros.hasta) && filtros.sobre === 'creacion') {
    parametros.campo_fecha = 'creacion'
  }
  poner('ordenar_por', filtros.orden)
  poner('direccion', filtros.dir)
  poner('page', filtros.pagina)
  poner('per_page', filtros.por)
  return parametros
}

/**
 * Campos del formulario → cuerpo de `POST` o `PATCH /incidentes`. Solo los presentes (en PATCH, los cambiados).
 * @param {Partial<{ titulo: string, descripcion: string, prioridad: string, categoria: string, estado: string,
 *   solucion: string, asignadoId: number | null, fechaVencimiento: string }>} campos
 * @returns {Record<string, unknown>}
 */
export function aCuerpoDeIncidente(campos) {
  /** @type {Record<string, unknown>} */
  const cuerpo = {}
  const nombres = /** @type {const} */ ({
    titulo: 'titulo',
    descripcion: 'descripcion',
    prioridad: 'prioridad',
    categoria: 'categoria',
    estado: 'estado',
    solucion: 'solucion',
    asignadoId: 'usuario_asignado_id',
    fechaVencimiento: 'fecha_vencimiento',
    fechaRequerida: 'fecha_requerida',
  })
  for (const [campo, nombreEnApi] of Object.entries(nombres)) {
    if (campo in campos) {
      cuerpo[nombreEnApi] = /** @type {any} */ (campos)[campo]
    }
  }
  return cuerpo
}

/** Campo de la API → campo del formulario, para colocar los errores 422 bajo su campo. */
export const CAMPOS_DEL_FORMULARIO = Object.freeze({
  titulo: 'titulo',
  descripcion: 'descripcion',
  prioridad: 'prioridad',
  categoria: 'categoria',
  estado: 'estado',
  solucion: 'solucion',
  usuario_asignado_id: 'asignadoId',
  fecha_vencimiento: 'fechaVencimiento',
})
