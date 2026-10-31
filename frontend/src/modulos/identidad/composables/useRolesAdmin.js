import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { clavesDeIdentidad } from '../api/claves.js'
import { asignarPermisos, crearRol, eliminarRol, listarPermisos, renombrarRol } from '../api/roles.js'
import { useSesion } from '../tienda/sesion.js'
import { useRoles } from './useUsuarios.js'

/**
 * Roles y permisos (RF-F43): el rol elegido vive en la dirección; sus permisos se editan en un borrador que se
 * guarda de una vez (la API reemplaza la lista completa). Si el rol es el de la persona, su perfil se recarga.
 */
export function useRolesAdmin() {
  const ruta = useRoute()
  const enrutador = useRouter()
  const queryClient = useQueryClient()
  const sesion = useSesion()
  const notificaciones = useNotificaciones()

  const roles = useRoles()
  const permisosExistentes = useQuery({
    queryKey: clavesDeIdentidad.permisos(),
    queryFn: ({ signal }) => listarPermisos(signal),
    staleTime: Infinity,
  })

  const idElegido = computed(() => Number(ruta.query.rol) || roles.data.value?.[0]?.id)
  const rol = computed(() => roles.data.value?.find((candidato) => candidato.id === idElegido.value))

  // Borrador de permisos del rol elegido, comparado con la copia tomada al empezar a editarlo. Un refresco de
  // la lista (al volver a la pestaña) no pisa cambios sin guardar; cambiar de rol sí reinicia.
  const base = ref(/** @type {Set<string>} */ (new Set()))
  const borrador = ref(/** @type {Set<string>} */ (new Set()))
  const cambios = computed(() => {
    let total = 0
    for (const clave of new Set([...base.value, ...borrador.value])) {
      total += Number(base.value.has(clave) !== borrador.value.has(clave))
    }
    return total
  })
  watch(
    () => [rol.value?.id, (rol.value?.permisos ?? []).join(',')],
    ([id], anterior) => {
      if (anterior === undefined || id !== anterior[0] || cambios.value === 0) {
        base.value = new Set(rol.value?.permisos ?? [])
        borrador.value = new Set(base.value)
      }
    },
    { immediate: true },
  )

  /**
   * @param {string} clave
   * @param {boolean} concedido
   * @returns {void}
   */
  function alternar(clave, concedido) {
    const nuevo = new Set(borrador.value)
    concedido ? nuevo.add(clave) : nuevo.delete(clave)
    borrador.value = nuevo
  }

  /** @returns {void} */
  function descartar() {
    base.value = new Set(rol.value?.permisos ?? [])
    borrador.value = new Set(base.value)
  }

  /**
   * @param {number} id
   * @returns {void}
   */
  function elegir(id) {
    enrutador.replace({ name: 'roles', query: { rol: String(id) } })
  }

  /** @param {number} [id] rol que cambió */
  async function trasCambiar(id) {
    await queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.roles() })
    if (id !== undefined && id === sesion.perfil?.rol.id) {
      await sesion.refrescarPerfil()
    }
  }

  const guardadoDePermisos = useMutation({
    mutationFn: () => asignarPermisos(/** @type {number} */ (idElegido.value), [...borrador.value].sort()),
    onSuccess: async (actualizado) => {
      base.value = new Set(actualizado.permisos)
      borrador.value = new Set(actualizado.permisos)
      notificaciones.exito(
        'Permisos guardados',
        `Se aplican de inmediato a las personas con el rol ${actualizado.nombre}.`,
      )
      await trasCambiar(actualizado.id)
    },
  })

  const creacion = useMutation({
    mutationFn: crearRol,
    onSuccess: async (nuevo) => {
      notificaciones.exito('Rol creado', nuevo.nombre)
      await trasCambiar()
      elegir(nuevo.id)
    },
  })

  const renombrado = useMutation({
    /** @param {string} nombre */
    mutationFn: (nombre) => renombrarRol(/** @type {number} */ (idElegido.value), nombre),
    onSuccess: async (actualizado) => {
      notificaciones.exito('Rol renombrado', actualizado.nombre)
      await trasCambiar(actualizado.id)
    },
  })

  const eliminacion = useMutation({
    mutationFn: () => eliminarRol(/** @type {number} */ (idElegido.value)),
    onSuccess: async () => {
      notificaciones.exito('Rol eliminado', rol.value?.nombre)
      enrutador.replace({ name: 'roles' })
      await trasCambiar()
    },
  })

  /**
   * Ejecuta una acción y devuelve el mensaje de error para mostrarlo junto a ella ('' si salió bien).
   * @param {() => Promise<unknown>} accion
   * @param {string[]} [campos]
   * @returns {Promise<string>}
   */
  const intentar = (accion, campos) =>
    accion().then(
      () => '',
      (causa) => mensajeDeError(causa, campos),
    )

  return {
    roles,
    permisosExistentes,
    rol,
    borrador,
    cambios,
    alternar,
    descartar,
    elegir,
    pendiente: {
      permisos: computed(() => guardadoDePermisos.isPending.value),
      nombre: computed(() => creacion.isPending.value || renombrado.isPending.value),
      eliminacion: computed(() => eliminacion.isPending.value),
    },
    guardarPermisos: () => intentar(() => guardadoDePermisos.mutateAsync()),
    /** @param {string} nombre */
    crear: (nombre) => intentar(() => creacion.mutateAsync(nombre), ['nombre']),
    /** @param {string} nombre */
    renombrar: (nombre) => intentar(() => renombrado.mutateAsync(nombre), ['nombre']),
    eliminar: () => intentar(() => eliminacion.mutateAsync()),
  }
}
