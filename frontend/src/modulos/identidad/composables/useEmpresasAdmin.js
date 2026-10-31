import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { aConsultaDeRuta, enteroPositivo, textoDeConsulta } from '@/compartido/utilidades/consulta.js'
import { clavesDeIdentidad } from '../api/claves.js'
import { crearCliente, modificarCliente, paginarClientes } from '../api/clientes.js'

/** @typedef {import('../tipos.js').Cliente} Cliente */

/**
 * Empresas cliente (F-10): búsqueda y página en la dirección. No se borran, se desactivan: los incidentes
 * guardan la empresa del momento en que se reportaron.
 */
export function useEmpresasAdmin() {
  const ruta = useRoute()
  const enrutador = useRouter()
  const queryClient = useQueryClient()
  const notificaciones = useNotificaciones()

  const criterio = computed(() => {
    const consulta = ruta.name === 'empresas' ? ruta.query : {}
    return { texto: textoDeConsulta(consulta.q), pagina: enteroPositivo(consulta.pagina) ?? 1, porPagina: 15 }
  })

  const lista = useQuery({
    queryKey: computed(() => clavesDeIdentidad.listaDeClientes({ ...criterio.value, administracion: true })),
    queryFn: ({ signal }) => paginarClientes(criterio.value, signal),
    placeholderData: keepPreviousData,
  })

  /**
   * @param {{ q?: string, pagina?: number }} cambios
   * @returns {void}
   */
  function cambiar(cambios) {
    const q = 'q' in cambios ? cambios.q : criterio.value.texto
    enrutador.push({ name: 'empresas', query: aConsultaDeRuta({ q, pagina: cambios.pagina }) })
  }

  // Cambiar una empresa afecta también a los selectores y a los filtros de incidentes y usuarios.
  const trasCambiar = () => queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.clientes() })

  const creacion = useMutation({
    mutationFn: crearCliente,
    onSuccess: async (empresa) => {
      notificaciones.exito('Empresa creada', empresa.nombre)
      await trasCambiar()
    },
  })

  const modificacion = useMutation({
    /** @param {{ empresa: Cliente, cambios: { nombre?: string, activo?: boolean } }} pedido */
    mutationFn: ({ empresa, cambios }) => modificarCliente(empresa.id, cambios),
    onSuccess: async (empresa, { cambios }) => {
      const titulo =
        'activo' in cambios ? (empresa.activo ? 'Empresa reactivada' : 'Empresa desactivada') : 'Empresa renombrada'
      notificaciones.exito(titulo, empresa.nombre)
      await trasCambiar()
    },
  })

  /**
   * Crea una empresa o renombra la indicada.
   * @param {Cliente | null} empresa
   * @param {string} nombre
   * @returns {Promise<string>} el mensaje de error para el campo; '' si salió bien
   */
  function guardarNombre(empresa, nombre) {
    const accion = empresa ? modificacion.mutateAsync({ empresa, cambios: { nombre } }) : creacion.mutateAsync(nombre)
    return accion.then(
      () => '',
      (causa) => mensajeDeError(causa, ['nombre']),
    )
  }

  /**
   * @param {Cliente} empresa
   * @returns {Promise<void>} avisa si la API lo impide
   */
  async function alternarActiva(empresa) {
    await modificacion
      .mutateAsync({ empresa, cambios: { activo: !empresa.activo } })
      .catch((causa) => notificaciones.error('No se pudo cambiar la empresa', mensajeDeError(causa, ['activo'])))
  }

  return {
    criterio,
    lista,
    cambiar,
    guardarNombre,
    alternarActiva,
    pendiente: computed(() => creacion.isPending.value || modificacion.isPending.value),
  }
}
