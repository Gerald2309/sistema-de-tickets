import { useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { refrescoDeRespaldo } from '@/compartido/tiempo-real.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { contarIncidentes, listarIncidentes, obtenerAvisos, obtenerResumen } from '../api/incidentes.js'
import { claves } from '../api/claves.js'

const HORA_MS = 60 * 60 * 1000

/** Pendientes que vencen en las próximas 24 h, los más urgentes primero (RF-F15). */
export const FILTROS_VENCEN_PRONTO = Object.freeze({ vence: 24, orden: 'fecha_vencimiento', dir: 'asc', por: 5 })

/**
 * Horas que faltan para el fin del día local (al menos 1), para «asignados a mí que vencen hoy».
 * @param {Date} [ahora]
 * @returns {number}
 */
export function horasHastaFinDelDia(ahora = new Date()) {
  const fin = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate(), 23, 59, 59)
  return Math.max(1, Math.ceil((fin.getTime() - ahora.getTime()) / HORA_MS))
}

/**
 * Caso de uso del panel (RF-F13 a RF-F18): resumen por etapa, vencimientos próximos, trabajo propio y avisos.
 * Cada parte es su propia consulta: si una falla, las demás siguen visibles.
 */
export function usePanel() {
  const sesion = useSesion()
  const queryClient = useQueryClient()
  const veTodo = computed(() => sesion.tiene('ver_todos'))
  const respaldo = refrescoDeRespaldo(30_000)

  const resumen = useQuery({
    queryKey: claves.panel,
    queryFn: ({ signal }) => obtenerResumen(signal),
    refetchInterval: respaldo,
  })

  const avisos = useQuery({
    queryKey: claves.avisos,
    queryFn: ({ signal }) => obtenerAvisos(signal),
    enabled: veTodo,
    staleTime: 30_000,
    refetchInterval: refrescoDeRespaldo(60_000),
  })

  const vencenPronto = useQuery({
    queryKey: claves.lista(FILTROS_VENCEN_PRONTO),
    queryFn: ({ signal }) => listarIncidentes(FILTROS_VENCEN_PRONTO, signal),
    refetchInterval: respaldo,
  })

  /**
   * @param {import('../tipos.js').FiltrosDeIncidentes} filtros
   * @param {import('vue').Ref<boolean> | boolean} [habilitado]
   */
  const conteo = (filtros, habilitado = true) =>
    useQuery({
      queryKey: claves.conteo(filtros),
      queryFn: ({ signal }) => contarIncidentes(filtros, signal),
      enabled: habilitado,
      refetchInterval: respaldo,
    })

  // Los filtros de «mi trabajo» son los mismos que abre cada enlace del listado.
  const filtrosMiTrabajo = {
    asignados: /** @type {import('../tipos.js').FiltrosDeIncidentes} */ ({ vista: 'para-mi' }),
    vencenHoy: /** @type {import('../tipos.js').FiltrosDeIncidentes} */ ({
      vista: 'para-mi',
      vence: horasHastaFinDelDia(),
    }),
    creados: /** @type {import('../tipos.js').FiltrosDeIncidentes} */ ({ mios: true }),
  }
  const asignados = conteo(filtrosMiTrabajo.asignados, veTodo)
  const vencenHoy = conteo(filtrosMiTrabajo.vencenHoy, veTodo)
  const creados = conteo(filtrosMiTrabajo.creados)

  const actualizadoEn = computed(() => resumen.dataUpdatedAt.value)

  /** @returns {Promise<void>} vuelve a pedir todo lo del panel */
  async function actualizar() {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: claves.panel }),
      queryClient.invalidateQueries({ queryKey: claves.avisos }),
      queryClient.invalidateQueries({ queryKey: claves.listas() }),
      queryClient.invalidateQueries({ queryKey: claves.conteos() }),
    ])
  }

  return {
    veTodo,
    resumen,
    avisos,
    vencenPronto,
    miTrabajo: { asignados, vencenHoy, creados, filtros: filtrosMiTrabajo },
    actualizadoEn,
    actualizar,
  }
}
