import { keepPreviousData, useQuery } from '@tanstack/vue-query'
import { computed, ref, shallowRef, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ErrorDeApi } from '@/compartido/api/errores.js'
import { refrescoDeRespaldo } from '@/compartido/tiempo-real.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { contarIncidentes, listarIncidentes } from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import { consultaDesdeFiltros, filtrosDesdeConsulta, POR_PAGINA } from '../filtros.js'

/** @typedef {import('../tipos.js').FiltrosDeIncidentes} FiltrosDeIncidentes */
/** @typedef {import('../tipos.js').Incidente} Incidente */

/** Orden por defecto de cada campo al elegirlo: lo más urgente primero. */
const SENTIDO_INICIAL = /** @type {const} */ ({
  fecha_vencimiento: 'asc',
  prioridad: 'desc',
  estado: 'asc',
  id: 'desc',
  creado: 'desc',
})

/**
 * Caso de uso del listado (RF-F19 a RF-F23): los filtros viven en la dirección; la tabla se pide al servidor
 * paginada y conserva lo anterior mientras llega lo nuevo; las vistas muestran su total.
 */
export function useListadoIncidentes() {
  const ruta = useRoute()
  const enrutador = useRouter()
  const sesion = useSesion()
  const veTodo = computed(() => sesion.tiene('ver_todos'))
  const respaldo = refrescoDeRespaldo(30_000)

  // Solo se atiende la dirección mientras la ruta activa es el listado: durante la transición hacia otra
  // pantalla, este componente sigue montado unos milisegundos y no debe leer ni corregir la consulta ajena.
  const consulta = shallowRef(ruta.query)
  watch(
    () => [ruta.name, ruta.query],
    () => {
      if (ruta.name === 'incidentes') {
        consulta.value = ruta.query
      }
    },
  )

  const filtros = computed(() => filtrosDesdeConsulta(consulta.value))
  const pedidos = computed(() => ({
    ...filtros.value,
    pagina: filtros.value.pagina ?? 1,
    por: filtros.value.por ?? POR_PAGINA,
  }))

  // Lo que no pasó la limpieza de filtros se quita de la dirección sin crear una entrada en el historial.
  watch(
    consulta,
    (actual) => {
      const limpia = consultaDesdeFiltros(filtros.value)
      if (ruta.name === 'incidentes' && JSON.stringify(limpia) !== JSON.stringify(actual)) {
        enrutador.replace({ name: 'incidentes', query: limpia })
      }
    },
    { immediate: true },
  )

  const listado = useQuery({
    queryKey: computed(() => claves.lista(pedidos.value)),
    queryFn: ({ signal }) => listarIncidentes(pedidos.value, signal),
    placeholderData: keepPreviousData,
    refetchInterval: respaldo,
  })

  // Un 422 de la búsqueda (sin palabras útiles) no vacía la tabla: se conserva el último resultado.
  const ultimaPagina = shallowRef(
    /** @type {import('@/compartido/api/paginacion.js').Pagina<Incidente> | undefined} */ (undefined),
  )
  watch(
    () => listado.data.value,
    (pagina) => {
      if (pagina) {
        ultimaPagina.value = pagina
      }
    },
  )
  const pagina = computed(() => listado.data.value ?? ultimaPagina.value)
  const errorDeBusqueda = computed(() => {
    const error = listado.error.value
    return error instanceof ErrorDeApi ? (error.erroresDe('q')[0] ?? '') : ''
  })
  const errorDeCarga = computed(() => (listado.isError.value && !errorDeBusqueda.value ? listado.error.value : null))

  // Totales de cada vista con los mismos filtros (brecha G-04: una fila por consulta).
  const base = computed(() => {
    const { vista: _vista, pagina: _pagina, por: _por, orden: _orden, dir: _dir, ...resto } = filtros.value
    return resto
  })
  /**
   * @param {import('vue').ComputedRef<FiltrosDeIncidentes>} filtrosDeVista
   * @param {import('vue').MaybeRefOrGetter<boolean>} [habilitado]
   */
  const conteo = (filtrosDeVista, habilitado = true) =>
    useQuery({
      queryKey: computed(() => claves.conteo(filtrosDeVista.value)),
      queryFn: ({ signal }) => contarIncidentes(filtrosDeVista.value, signal),
      enabled: habilitado,
      placeholderData: keepPreviousData,
      refetchInterval: respaldo,
    })
  const conteos = {
    todos: conteo(base),
    paraMi: conteo(
      computed(() => {
        const { asignado: _asignado, ...resto } = base.value
        return { ...resto, vista: /** @type {const} */ ('para-mi') }
      }),
      veTodo,
    ),
    vencidos: conteo(
      computed(() => {
        const { vence: _vence, ...resto } = base.value
        return { ...resto, vista: /** @type {const} */ ('vencidos') }
      }),
    ),
  }

  /**
   * Cambia filtros, orden o página. Cualquier cambio que no sea de página vuelve a la primera.
   * @param {Partial<FiltrosDeIncidentes>} cambios
   * @param {{ reemplazar?: boolean }} [opciones] la búsqueda reemplaza la entrada del historial
   * @returns {void}
   */
  function cambiar(cambios, { reemplazar = false } = {}) {
    const nuevos = { ...filtros.value, ...cambios }
    if (!('pagina' in cambios)) {
      delete nuevos.pagina
    }
    const destino = { name: 'incidentes', query: consultaDesdeFiltros(nuevos) }
    reemplazar ? enrutador.replace(destino) : enrutador.push(destino)
  }

  /**
   * @param {keyof FiltrosDeIncidentes} clave
   * @returns {void}
   */
  function quitar(clave) {
    cambiar({ [clave]: undefined })
  }

  /** @returns {void} quita los filtros y conserva vista, orden y tamaño de página */
  function limpiar() {
    const { vista, orden, dir, por } = filtros.value
    enrutador.push({ name: 'incidentes', query: consultaDesdeFiltros({ vista, orden, dir, por }) })
  }

  /**
   * Un clic en un encabezado ordena por ese campo; otro clic invierte el sentido (RF-F23).
   * @param {NonNullable<FiltrosDeIncidentes['orden']>} campo
   * @returns {void}
   */
  function ordenar(campo) {
    const actual = filtros.value.orden ?? 'id'
    const dir =
      actual === campo
        ? (filtros.value.dir ?? SENTIDO_INICIAL[campo]) === 'asc'
          ? 'desc'
          : 'asc'
        : SENTIDO_INICIAL[campo]
    cambiar({ orden: campo, dir })
  }

  // Selección para el lote: se limpia al cambiar de filtros o de página (el lote exige ids visibles).
  const seleccion = ref(/** @type {Set<number>} */ (new Set()))
  watch(pedidos, () => (seleccion.value = new Set()), { deep: true })

  /**
   * @param {number} id
   * @returns {void}
   */
  function alternar(id) {
    const nueva = new Set(seleccion.value)
    nueva.has(id) ? nueva.delete(id) : nueva.add(id)
    seleccion.value = nueva
  }
  const idsVisibles = computed(() => pagina.value?.elementos.map((incidente) => incidente.id) ?? [])
  const todosSeleccionados = computed(
    () => idsVisibles.value.length > 0 && idsVisibles.value.every((id) => seleccion.value.has(id)),
  )
  /** @returns {void} */
  function alternarTodos() {
    seleccion.value = todosSeleccionados.value ? new Set() : new Set(idsVisibles.value)
  }
  /** @returns {void} */
  function limpiarSeleccion() {
    seleccion.value = new Set()
  }

  return {
    filtros,
    pedidos,
    veTodo,
    listado,
    pagina,
    cargando: computed(() => listado.isPending.value),
    actualizando: computed(() => listado.isFetching.value),
    actualizadoEn: computed(() => listado.dataUpdatedAt.value),
    errorDeBusqueda,
    errorDeCarga,
    conteos,
    cambiar,
    quitar,
    limpiar,
    ordenar,
    seleccion,
    alternar,
    alternarTodos,
    todosSeleccionados,
    limpiarSeleccion,
  }
}
