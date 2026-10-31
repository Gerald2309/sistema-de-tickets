import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import { listarIncidentes } from '../api/incidentes.js'
import { claves } from '../api/claves.js'

/** Lo que sigue vivo para quien lo reportó. */
const ESTADOS_VIGENTES = /** @type {const} */ (['abierto', 'en_progreso', 'resuelto'])
const POR_ESTADO = 50
const CERRADAS_RECIENTES = 20

/**
 * @param {string} estado
 * @param {number} por
 * @returns {import('../tipos.js').FiltrosDeIncidentes}
 */
const misDe = (estado, por) => ({ mios: true, estado, por, orden: 'id', dir: 'desc' })

/**
 * @param {import('../tipos.js').Incidente} a
 * @param {import('../tipos.js').Incidente} b
 * @returns {number} lo movido más recientemente primero
 */
const porActividad = (a, b) => b.actualizadoEn.localeCompare(a.actualizadoEn)

/**
 * Inicio de quien reporta incidentes. Lo que le toca hacer va primero: confirmar las soluciones; en lo que sigue
 * en curso, las que tienen respuestas sin leer encabezan la lista. Las cerradas se piden solo al verlas.
 * @param {import('vue').MaybeRefOrGetter<boolean>} verCerradas
 */
export function useMisSolicitudes(verCerradas) {
  const vigentes = ESTADOS_VIGENTES.map((estado) =>
    useQuery({
      queryKey: claves.lista(misDe(estado, POR_ESTADO)),
      queryFn: ({ signal }) => listarIncidentes(misDe(estado, POR_ESTADO), signal),
    }),
  )
  const cerradas = useQuery({
    queryKey: claves.lista(misDe('cerrado', CERRADAS_RECIENTES)),
    queryFn: ({ signal }) => listarIncidentes(misDe('cerrado', CERRADAS_RECIENTES), signal),
    enabled: computed(() => toValue(verCerradas)),
  })

  const todas = computed(() => vigentes.flatMap((consulta) => consulta.data.value?.elementos ?? []))

  return {
    porConfirmar: computed(() => todas.value.filter((incidente) => incidente.estado === 'resuelto').sort(porActividad)),
    enCurso: computed(() =>
      todas.value
        .filter((incidente) => incidente.estado !== 'resuelto')
        .sort((a, b) => Number(b.mensajesSinLeer > 0) - Number(a.mensajesSinLeer > 0) || porActividad(a, b)),
    ),
    cerradas: computed(() => cerradas.data.value?.elementos ?? []),
    cargandoCerradas: computed(() => cerradas.isPending.value),
    cargando: computed(() => vigentes.some((consulta) => consulta.isPending.value)),
    error: computed(() => vigentes.find((consulta) => consulta.error.value)?.error.value ?? null),
    // Con más de las que se piden en algún estado, la lista está recortada: el listado completo las tiene todas.
    recortada: computed(() =>
      vigentes.some((consulta) => (consulta.data.value?.total ?? 0) > (consulta.data.value?.elementos.length ?? 0)),
    ),
    reintentar: () => vigentes.forEach((consulta) => consulta.refetch()),
  }
}
