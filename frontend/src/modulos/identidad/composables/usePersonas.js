import { keepPreviousData, useQuery } from '@tanstack/vue-query'
import { computed, ref, toValue } from 'vue'
import { clavesDeIdentidad } from '../api/claves.js'
import { listarClientes } from '../api/clientes.js'
import { buscarPersonas } from '../api/personas.js'
import { useSesion } from '../tienda/sesion.js'

/**
 * Selector de personas (asignar, filtrar): busca mientras se escribe y resuelve los nombres de los ids ya
 * elegidos (por ejemplo, los de un filtro que viene en la dirección).
 * @param {{ incluirInactivas?: boolean, elegidos?: import('vue').MaybeRefOrGetter<number[]> }} [opciones]
 */
export function useBuscadorDePersonas(opciones = {}) {
  const sesion = useSesion()
  const texto = ref('')
  const permitido = computed(() =>
    sesion.tieneAlguno('reasignar', 'ver_todos', 'ver_auditoria', 'administrar_usuarios'),
  )

  const coincidencias = useQuery({
    queryKey: computed(() =>
      clavesDeIdentidad.busquedaDePersonas({ texto: texto.value, inactivas: !!opciones.incluirInactivas }),
    ),
    queryFn: ({ signal }) =>
      buscarPersonas({ texto: texto.value, incluirInactivas: opciones.incluirInactivas }, signal),
    enabled: permitido,
    staleTime: 60_000,
    placeholderData: keepPreviousData,
  })

  const ids = computed(() => toValue(opciones.elegidos) ?? [])
  const elegidas = useQuery({
    queryKey: computed(() => clavesDeIdentidad.busquedaDePersonas({ ids: ids.value })),
    queryFn: ({ signal }) => buscarPersonas({ ids: ids.value }, signal),
    enabled: computed(() => permitido.value && ids.value.length > 0),
    staleTime: 5 * 60_000,
  })

  /** Personas disponibles para mostrar: las encontradas más las ya elegidas, sin repetir. */
  const personas = computed(() => {
    const todas = [...(elegidas.data.value ?? []), ...(coincidencias.data.value ?? [])]
    return todas.filter((persona, indice) => todas.findIndex((otra) => otra.id === persona.id) === indice)
  })

  /**
   * @param {number | undefined} id
   * @returns {string | undefined}
   */
  const nombreDe = (id) => personas.value.find((persona) => persona.id === id)?.nombre

  return { texto, personas, cargando: coincidencias.isFetching, permitido, nombreDe }
}

/** Empresas cliente activas, para filtros (G-03). */
export function useEmpresas() {
  const sesion = useSesion()
  const permitido = computed(() => sesion.tieneAlguno('administrar_usuarios', 'ver_todos'))
  const consulta = useQuery({
    queryKey: clavesDeIdentidad.listaDeClientes({ activo: true }),
    queryFn: ({ signal }) => listarClientes({ activo: true }, signal),
    enabled: permitido,
    staleTime: 5 * 60_000,
  })
  const empresas = computed(() => consulta.data.value ?? [])
  /**
   * @param {number | undefined} id
   * @returns {string | undefined}
   */
  const nombreDe = (id) => empresas.value.find((empresa) => empresa.id === id)?.nombre
  return { empresas, permitido, nombreDe }
}
