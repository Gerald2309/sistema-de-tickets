import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { aErrorDeApi, ErrorDeApi } from '@/compartido/api/errores.js'
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
} from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import { partesEditables } from '../permisos.js'
import { presentarEstado } from '../presentacion.js'
import { usePestanas } from '../tienda/pestanas.js'
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
 * permisos la permiten, y las acciones de cambiar estado y eliminar.
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

  const historial = useQuery({
    queryKey: computed(() => claves.historial(id.value)),
    queryFn: ({ signal }) => obtenerHistorial(id.value, signal),
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
      const problema = aErrorDeApi(causa)
      errorDeEstado.value = [...problema.erroresDe('solucion'), ...problema.erroresDe('estado')][0] ?? problema.detalle
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
      notificaciones.error('No se eliminó', aErrorDeApi(causa).detalle)
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
    similares,
    otrosDelSolicitante,
    empresa,
    partes: computed(() =>
      incidente.value
        ? partesEditables(sesion, incidente.value)
        : { contenido: false, estado: false, asignacion: false },
    ),
    puedeEliminar: computed(() => sesion.tiene('borrar')),
    cambiarEstado,
    cambiandoEstado: computed(() => cambioDeEstado.isPending.value),
    errorDeEstado,
    eliminar,
    eliminando: computed(() => eliminacion.isPending.value),
  }
}
