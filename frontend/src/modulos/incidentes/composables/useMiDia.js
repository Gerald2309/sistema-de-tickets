import { keepPreviousData, useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, onScopeDispose, ref, watch } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { claves } from '../api/claves.js'
import * as api from '../api/mi-dia.js'
import { JORNADA, aTramo, cuadriculaDelMes, diaLocal, minutoDelDia, semanaLaboral, sumarDias } from '../mi-dia.js'

/** @typedef {import('../api/mi-dia.js').EntradaDelPlan} EntradaDelPlan */
/** @typedef {import('../api/mi-dia.js').NotaPersonal} NotaPersonal */
/** @typedef {import('../api/mi-dia.js').IncidenteDelPlan} IncidenteDelPlan */
/** @typedef {'dia' | 'semana' | 'mes'} VistaDelPlan */
/** @typedef {{ tipo: 'incidente' | 'nota', id: number }} Planeable */

const UN_MINUTO_MS = 60_000

/**
 * «Mi día» de quien atiende incidentes: la agenda del período a la vista, la bandeja de lo por planear, la
 * pizarra de notas y lo que se hace con ellas. Cada escritura avisa y, cuando se puede, ofrece deshacerla.
 */
export function useMiDia() {
  const queryClient = useQueryClient()
  const notificaciones = useNotificaciones()

  // La hora avanza sola: «ahora», la línea roja y lo que vence hoy siguen al reloj.
  const ahora = ref(new Date())
  const reloj = setInterval(() => (ahora.value = new Date()), UN_MINUTO_MS)
  onScopeDispose(() => clearInterval(reloj))

  const hoy = computed(() => diaLocal(ahora.value))
  const minutoActual = computed(() => minutoDelDia(ahora.value))
  // Desde dónde se buscan huecos hoy: la media hora siguiente a la actual.
  const desdeAhora = computed(() => aTramo(minutoActual.value) + JORNADA.paso)

  const vista = ref(/** @type {VistaDelPlan} */ ('dia'))
  const dia = ref(hoy.value)
  const rango = computed(() => {
    if (vista.value === 'semana') {
      const semana = semanaLaboral(dia.value)
      return { desde: semana[0], hasta: semana[4] }
    }
    if (vista.value === 'mes') {
      const casillas = cuadriculaDelMes(dia.value)
      return { desde: casillas[0], hasta: casillas[casillas.length - 1] }
    }
    return { desde: dia.value, hasta: dia.value }
  })

  const plan = useQuery({
    queryKey: computed(() => claves.plan(rango.value.desde, rango.value.hasta)),
    queryFn: ({ signal }) => api.obtenerPlan(rango.value.desde, rango.value.hasta, signal),
    placeholderData: keepPreviousData,
  })
  const buscar = ref('')
  const porPlanear = useInfiniteQuery({
    queryKey: computed(() => claves.porPlanear(buscar.value)),
    queryFn: ({ pageParam, signal }) => api.obtenerPorPlanear(buscar.value, pageParam, signal),
    initialPageParam: /** @type {string | null} */ (null),
    getNextPageParam: (tramo) => tramo.siguiente,
    placeholderData: keepPreviousData,
  })
  const pendientes = useQuery({
    queryKey: computed(() => claves.pendientes(hoy.value)),
    queryFn: ({ signal }) => api.obtenerPendientes(hoy.value, signal),
  })
  const notas = useQuery({
    queryKey: computed(() => claves.notas(hoy.value)),
    queryFn: ({ signal }) => api.obtenerNotas(hoy.value, signal),
  })
  const ultimo = useQuery({
    queryKey: claves.ultimoTrabajado,
    queryFn: ({ signal }) => api.obtenerUltimoTrabajado(signal),
  })

  // Al cambiar de día (pasó la medianoche con la vista abierta), la agenda sigue a hoy si estaba en hoy.
  watch(hoy, (nuevo, anterior) => dia.value === anterior && (dia.value = nuevo))

  const entradas = computed(() => plan.data.value ?? [])
  const delDia = computed(() => entradas.value.filter((entrada) => entrada.fecha === dia.value))
  const porPlanearLista = computed(() => porPlanear.data.value?.pages.flatMap((tramo) => tramo.elementos) ?? [])

  const refrescar = () => queryClient.invalidateQueries({ queryKey: claves.miDia })

  const escritura = useMutation({
    /** @param {{ hacer: () => Promise<unknown> }} pedido */
    mutationFn: ({ hacer }) => hacer(),
    onSettled: refrescar,
  })

  /**
   * Hace una escritura y avisa. Si falla, el aviso dice por qué (por ejemplo, que no cabe ese día).
   * @template T
   * @param {() => Promise<T>} hacer
   * @param {string} fallo título del aviso de error
   * @returns {Promise<T | null>} el resultado, o null si falló
   */
  async function escribir(hacer, fallo) {
    try {
      return /** @type {T} */ (await escritura.mutateAsync({ hacer }))
    } catch (causa) {
      notificaciones.error(fallo, mensajeDeError(causa))
      return null
    }
  }

  /**
   * Pone un incidente o una nota en el día, en el primer hueco desde `inicio` (por defecto, desde ahora si es
   * hoy o desde el comienzo de la jornada).
   * @param {Planeable} que
   * @param {string} [fecha]
   * @param {number} [inicio]
   * @returns {Promise<EntradaDelPlan | null>}
   */
  function planear(que, fecha = dia.value, inicio) {
    const desde = inicio ?? (fecha === hoy.value ? desdeAhora.value : JORNADA.inicio)
    return escribir(
      () =>
        api.planear({
          fecha,
          inicio: Math.min(Math.max(desde, JORNADA.temprano), JORNADA.fin - JORNADA.paso),
          ...(que.tipo === 'incidente' ? { incidenteId: que.id } : { notaId: que.id }),
        }),
      'No se pudo planear',
    )
  }

  /**
   * @param {EntradaDelPlan} entrada
   * @param {{ fecha?: string, inicio?: number, duracion?: number }} cambios
   * @returns {Promise<EntradaDelPlan | null>}
   */
  function reubicar(entrada, cambios) {
    return escribir(() => api.reubicar(entrada.id, cambios), 'No se pudo mover')
  }

  /**
   * Quita una entrada de la agenda; el aviso permite deshacerlo en su mismo lugar.
   * @param {EntradaDelPlan} entrada
   * @returns {Promise<void>}
   */
  async function quitar(entrada) {
    if ((await escribir(() => api.quitarDelPlan(entrada.id), 'No se pudo quitar')) === null) {
      return
    }
    const que = entrada.incidente ? { incidenteId: entrada.incidente.id } : { notaId: entrada.nota?.id }
    notificaciones.info(
      entrada.incidente ? `${entrada.incidente.codigo} volvió a la bandeja` : 'La nota volvió a la pizarra',
      undefined,
      {
        texto: 'Deshacer',
        alPulsar: () =>
          void escribir(
            () => api.planear({ fecha: entrada.fecha, inicio: entrada.inicio, duracion: entrada.duracion, ...que }),
            'No se pudo deshacer',
          ),
      },
    )
  }

  /** @returns {Promise<void>} */
  async function sugerir() {
    const desde = dia.value === hoy.value ? desdeAhora.value : JORNADA.inicio
    const agregadas = await escribir(() => api.sugerirDia(dia.value, desde), 'No se pudo sugerir el día')
    if (agregadas === null) {
      return
    }
    if (agregadas.length === 0) {
      notificaciones.info('Su día ya está completo', 'No queda nada urgente sin planear o ya no hay huecos.')
      return
    }
    notificaciones.exito(`Se agregaron ${agregadas.length} al día`, 'Lo más urgente, en los huecos libres.', {
      texto: 'Deshacer',
      alPulsar: () =>
        void escribir(
          () => Promise.all(agregadas.map((entrada) => api.quitarDelPlan(entrada.id))),
          'No se pudo deshacer',
        ),
    })
  }

  /**
   * @param {string} texto
   * @param {number} [inicio] con hora, la nota va directo al día a la vista
   * @returns {Promise<NotaPersonal | null>}
   */
  async function crearNota(texto, inicio) {
    const nota = await escribir(() => api.crearNota(texto), 'No se guardó la nota')
    if (nota && inicio !== undefined) {
      await planear({ tipo: 'nota', id: nota.id }, dia.value, inicio)
    }
    return nota
  }

  /**
   * @param {NotaPersonal} nota
   * @param {boolean} hecha
   * @returns {Promise<void>}
   */
  async function marcarNota(nota, hecha) {
    await escribir(() => api.cambiarNota(nota.id, { hecha }), 'No se guardó la nota')
  }

  /**
   * @param {NotaPersonal} nota
   * @returns {Promise<void>}
   */
  async function borrarNota(nota) {
    if ((await escribir(() => api.borrarNota(nota.id), 'No se borró la nota')) === null) {
      return
    }
    notificaciones.info('Nota borrada', undefined, {
      texto: 'Deshacer',
      alPulsar: () =>
        void escribir(async () => {
          const devuelta = await api.crearNota(nota.texto)
          if (nota.hecha) {
            await api.cambiarNota(devuelta.id, { hecha: true })
          }
        }, 'No se pudo deshacer'),
    })
  }

  /**
   * @param {IncidenteDelPlan} incidente
   * @param {string | null} motivo sin motivo, se quita la marca
   * @returns {Promise<void>}
   */
  async function marcarBloqueo(incidente, motivo) {
    await escribir(() => api.marcarBloqueo(incidente.id, motivo), 'No se guardó la marca')
  }

  /**
   * Pasa a la vista de un día (desde la semana o el mes) o se mueve día a día.
   * @param {string} nuevo
   * @returns {void}
   */
  function irADia(nuevo) {
    dia.value = nuevo
    vista.value = 'dia'
  }

  /**
   * Avanza o retrocede un período de la vista actual.
   * @param {1 | -1} sentido
   * @returns {void}
   */
  function moverPeriodo(sentido) {
    if (vista.value === 'mes') {
      const [anio, mes] = dia.value.split('-').map(Number)
      const destino = new Date(anio, mes - 1 + sentido, 1)
      dia.value = diaLocal(destino)
      return
    }
    dia.value = sumarDias(dia.value, vista.value === 'semana' ? 7 * sentido : sentido)
  }

  return {
    ahora,
    hoy,
    minutoActual,
    vista,
    dia,
    plan,
    entradas,
    delDia,
    buscar,
    porPlanear,
    porPlanearLista,
    pendientes,
    notas,
    ultimo,
    escribiendo: computed(() => escritura.isPending.value),
    planear,
    reubicar,
    quitar,
    sugerir,
    crearNota,
    marcarNota,
    borrarNota,
    marcarBloqueo,
    irADia,
    moverPeriodo,
  }
}
