import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ErrorDeApi, mensajeDeError } from '@/compartido/api/errores.js'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { useEmpresas, useSesion } from '@/modulos/identidad/index.js'
import {
  actualizarIncidente,
  contarIncidentes,
  eliminarIncidente,
  listarIncidentes,
  obtenerHistorial,
  obtenerIncidente,
  obtenerSimilares,
  tomarIncidente,
} from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import {
  NADA_EDITABLE,
  partesEditables,
  puedeConfirmarSolucion,
  puedeDejarSinAsignar,
  puedeTomar,
} from '../permisos.js'
import { presentarEstado } from '../presentacion.js'
import { usePestanas } from '../tienda/pestanas.js'
import { useConfirmacionDeSolucion } from './useConfirmacionDeSolucion.js'
import { useInvalidacion } from './useInvalidacion.js'

/**
 * Una sola definición de la consulta del detalle, para leerla y para precargarla (DRY).
 * @param {number} id
 */
export const consultaDeDetalle = (id) => ({
  queryKey: claves.detalle(id),
  /** @param {{ signal: AbortSignal }} contexto */
  queryFn: ({ signal }) => obtenerIncidente(id, signal),
})

/**
 * Precarga el detalle al pasar el puntero por una fila: al hacer clic ya está en caché (RNF-F04).
 * @returns {(id: number) => void}
 */
export function usePrecargaDeIncidente() {
  const queryClient = useQueryClient()
  return (id) => {
    queryClient.prefetchQuery(consultaDeDetalle(id))
  }
}

/**
 * Caso de uso del detalle (RF-F27 a RF-F34): el incidente y lo que lo rodea, cada parte pedida solo si los
 * permisos la permiten, y las acciones de cambiar estado, clasificar, reasignar, confirmar la solución y eliminar.
 * @param {() => number} idActual
 */
export function useDetalleIncidente(idActual) {
  const sesion = useSesion()
  const pestanas = usePestanas()
  const notificaciones = useNotificaciones()
  const confirmacion = useConfirmacion()
  const enrutador = useRouter()
  const { trasCambiarIncidentes, trasEliminarIncidentes } = useInvalidacion()
  const { permitido: veEmpresas } = useEmpresas()
  const veTodo = computed(() => sesion.tiene('ver_todos'))

  const id = computed(idActual)
  const detalle = useQuery(computed(() => consultaDeDetalle(id.value)))
  const incidente = computed(() => detalle.data.value)

  // La pestaña muestra código, título y estado en cuanto llegan.
  watch(incidente, (actual) => actual && pestanas.describir(actual.id, actual), { immediate: true })

  // Borrado o sin acceso: la pestaña se cierra con aviso (RF-F11).
  watch(
    () => detalle.error.value,
    (error) => {
      if (error instanceof ErrorDeApi && (error.estado === 404 || error.estado === 403)) {
        const codigo = pestanas.buscar(id.value)?.codigo ?? 'El incidente'
        pestanas.cerrar(id.value)
        notificaciones.info(`${codigo} no está disponible`, 'Fue eliminado o ya no tiene acceso a él.')
        enrutador.replace({ name: 'incidentes' })
      }
    },
  )

  // Por tramos, lo más reciente primero: «Ver más» trae los cambios anteriores.
  const historial = useInfiniteQuery({
    queryKey: computed(() => claves.historial(id.value)),
    queryFn: ({ pageParam, signal }) => obtenerHistorial(id.value, pageParam, signal),
    initialPageParam: /** @type {string | null} */ (null),
    getNextPageParam: (tramo) => tramo.siguiente,
    enabled: computed(() => sesion.tiene('ver_historial')),
  })
  const similares = useQuery({
    queryKey: computed(() => claves.similares(id.value)),
    queryFn: ({ signal }) => obtenerSimilares(id.value, signal),
    enabled: veTodo,
    staleTime: 5 * 60_000,
  })

  // Otros del mismo solicitante (F-11), sin el actual: se piden 5 para mostrar hasta 4.
  const filtrosDeOtros = computed(() => ({
    creador: incidente.value?.creador.id,
    por: 5,
    orden: /** @type {const} */ ('id'),
    dir: /** @type {const} */ ('desc'),
  }))
  const otros = useQuery({
    queryKey: computed(() => claves.lista(filtrosDeOtros.value)),
    queryFn: ({ signal }) => listarIncidentes(filtrosDeOtros.value, signal),
    enabled: computed(() => veTodo.value && incidente.value !== undefined),
  })
  const otrosDelSolicitante = computed(() => ({
    elementos: (otros.data.value?.elementos ?? []).filter((otro) => otro.id !== id.value).slice(0, 4),
    total: Math.max((otros.data.value?.total ?? 0) - 1, 0),
  }))

  // Resumen de la empresa (F-10): total y cuántos siguen sin resolver.
  const clienteId = computed(() => incidente.value?.cliente?.id)
  const conteoDeEmpresa = (/** @type {string | undefined} */ estado) =>
    useQuery({
      queryKey: computed(() => claves.conteo({ cliente: clienteId.value, estado })),
      queryFn: ({ signal }) => contarIncidentes({ cliente: clienteId.value, estado }, signal),
      enabled: computed(() => veEmpresas.value && clienteId.value !== undefined),
    })
  const deEmpresa = {
    total: conteoDeEmpresa(undefined),
    abiertos: conteoDeEmpresa('abierto'),
    enProgreso: conteoDeEmpresa('en_progreso'),
  }
  const empresa = computed(() =>
    deEmpresa.total.data.value === undefined
      ? null
      : {
          total: deEmpresa.total.data.value,
          sinResolver: (deEmpresa.abiertos.data.value ?? 0) + (deEmpresa.enProgreso.data.value ?? 0),
        },
  )

  const errorDeEstado = ref('')
  const cambioDeEstado = useMutation({
    /** @param {{ estado: string, solucion?: string }} cambios */
    mutationFn: (cambios) => actualizarIncidente(id.value, cambios),
    onSuccess: async (actualizado, { estado }) => {
      errorDeEstado.value = ''
      notificaciones.exito(`${actualizado.codigo} ${presentarEstado(estado).nombre.toLowerCase()}`)
      await trasCambiarIncidentes([actualizado.id])
    },
    onError: (causa) => {
      errorDeEstado.value = mensajeDeError(causa, ['solucion', 'estado'])
      notificaciones.error('No se cambió el estado', errorDeEstado.value)
    },
  })

  /**
   * @param {string} estado
   * @param {string} [solucion] obligatoria al resolver
   * @returns {Promise<boolean>} se aplicó
   */
  async function cambiarEstado(estado, solucion) {
    return cambioDeEstado
      .mutateAsync(solucion === undefined ? { estado } : { estado, solucion })
      .then(() => true)
      .catch(() => false)
  }

  // Clasificar y reasignar desde las propiedades, sin pasar por el formulario.
  const ajuste = useMutation({
    /** @param {{ prioridad?: string, categoria?: string, fechaVencimiento?: string, asignadoId?: number | null }} cambios */
    mutationFn: (cambios) => actualizarIncidente(id.value, cambios),
    onSuccess: async (actualizado) => {
      notificaciones.exito('Cambios guardados', actualizado.codigo)
      await trasCambiarIncidentes([actualizado.id])
    },
    onError: (causa) => notificaciones.error('No se guardó el cambio', mensajeDeError(causa)),
  })

  /**
   * @param {Parameters<typeof ajuste.mutateAsync>[0]} cambios
   * @returns {Promise<boolean>} se aplicó
   */
  async function ajustar(cambios) {
    return ajuste
      .mutateAsync(cambios)
      .then(() => true)
      .catch(() => false)
  }

  // Quien lo reportó dice si la solución sirvió: lo cierra o lo devuelve a soporte.
  const confirmacionDeSolucion = useConfirmacionDeSolucion()

  const toma = useMutation({
    mutationFn: () => tomarIncidente(id.value),
    onSuccess: async (tomado) => {
      notificaciones.exito(`${tomado.codigo} es suyo`, 'Ya puede trabajarlo y responder al solicitante.')
      await trasCambiarIncidentes([tomado.id])
    },
    // Un 409 dice por qué no (alguien lo tomó primero); se refresca para mostrar quién lo atiende.
    onError: async (causa) => {
      notificaciones.error('No se pudo tomar', mensajeDeError(causa))
      await trasCambiarIncidentes([id.value])
    },
  })

  const eliminacion = useMutation({ mutationFn: () => eliminarIncidente(id.value) })

  /** @returns {Promise<void>} pide confirmación; después cierra la pestaña y vuelve al listado */
  async function eliminar() {
    const codigo = incidente.value?.codigo ?? 'el incidente'
    const confirmado = await confirmacion.pedir({
      titulo: `¿Eliminar ${codigo}?`,
      texto: 'Deja de verse en el listado y su historial se conserva.',
      accion: 'Eliminar',
      peligrosa: true,
    })
    if (!confirmado) {
      return
    }
    try {
      await eliminacion.mutateAsync()
    } catch (causa) {
      notificaciones.error('No se eliminó', mensajeDeError(causa))
      return
    }
    const eliminado = id.value
    pestanas.cerrar(eliminado)
    notificaciones.exito(`${codigo} eliminado`)
    await enrutador.push({ name: 'incidentes' })
    await trasEliminarIncidentes([eliminado])
  }

  return {
    detalle,
    incidente,
    veTodo,
    historial,
    entradasDelHistorial: computed(() => historial.data.value?.pages.flatMap((tramo) => tramo.elementos)),
    similares,
    otrosDelSolicitante,
    empresa,
    partes: computed(() => (incidente.value ? partesEditables(sesion, incidente.value) : NADA_EDITABLE)),
    puedeDejarSinAsignar: computed(() => puedeDejarSinAsignar(sesion)),
    puedeTomar: computed(() => incidente.value !== undefined && puedeTomar(sesion, incidente.value)),
    puedeConfirmar: computed(() => incidente.value !== undefined && puedeConfirmarSolucion(sesion, incidente.value)),
    /** @param {Parameters<typeof confirmacionDeSolucion.confirmar>[1]} respuesta */
    confirmar: (respuesta) => confirmacionDeSolucion.confirmar(id.value, respuesta),
    confirmando: confirmacionDeSolucion.confirmando,
    tomar: () => toma.mutateAsync().catch(() => {}),
    tomando: computed(() => toma.isPending.value),
    puedeEliminar: computed(() => sesion.tiene('borrar')),
    cambiarEstado,
    ajustar,
    ajustando: computed(() => ajuste.isPending.value),
    cambiandoEstado: computed(() => cambioDeEstado.isPending.value),
    errorDeEstado,
    eliminar,
    eliminando: computed(() => eliminacion.isPending.value),
  }
}
