import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import {
  aConsultaDeRuta,
  conCambios,
  enteroPositivo,
  sinIndefinidos,
  textoDeConsulta,
} from '@/compartido/utilidades/consulta.js'
import { clavesDeIdentidad } from '../api/claves.js'
import { listarRoles } from '../api/roles.js'
import { asignarRol, crearUsuario, listarUsuarios, modificarUsuario } from '../api/usuarios.js'
import { useSesion } from '../tienda/sesion.js'

/** @typedef {import('../tipos.js').FiltrosDeUsuarios} FiltrosDeUsuarios */
/** @typedef {import('../tipos.js').Usuario} Usuario */

/**
 * Filtros de usuarios ↔ dirección, como en el listado de incidentes: recargar o compartir reproduce la vista.
 * @param {import('vue-router').LocationQuery} consulta
 * @returns {FiltrosDeUsuarios}
 */
export function filtrosDeUsuarios(consulta) {
  const estado = consulta.estado === 'activos' || consulta.estado === 'inactivos' ? consulta.estado : undefined
  return sinIndefinidos({
    q: textoDeConsulta(consulta.q),
    rol: enteroPositivo(consulta.rol),
    cliente: enteroPositivo(consulta.cliente),
    estado,
    pagina: enteroPositivo(consulta.pagina),
  })
}

/** Roles para filtros y selectores: quien administra usuarios los consulta para asignarlos. */
export function useRoles() {
  return useQuery({
    queryKey: clavesDeIdentidad.roles(),
    queryFn: ({ signal }) => listarRoles(signal),
    staleTime: 60_000,
  })
}

/** Listado de usuarios (RF-F41): filtros en la dirección, paginado en el servidor y con el total de cada vista. */
export function useListadoUsuarios() {
  const ruta = useRoute()
  const enrutador = useRouter()
  const filtros = computed(() => filtrosDeUsuarios(ruta.name === 'usuarios' ? ruta.query : {}))

  const lista = useQuery({
    queryKey: computed(() => clavesDeIdentidad.listaDeUsuarios(filtros.value)),
    queryFn: ({ signal }) => listarUsuarios(filtros.value, signal),
    placeholderData: keepPreviousData,
  })

  /** @param {FiltrosDeUsuarios['estado']} estado */
  const conteo = (estado) =>
    useQuery({
      queryKey: computed(() =>
        clavesDeIdentidad.listaDeUsuarios({ ...filtros.value, estado, pagina: undefined, conteo: true }),
      ),
      queryFn: async ({ signal }) => (await listarUsuarios({ ...filtros.value, estado, pagina: 1 }, signal, 1)).total,
      placeholderData: keepPreviousData,
    })
  const conteos = { todos: conteo(undefined), activos: conteo('activos'), inactivos: conteo('inactivos') }

  /**
   * @param {Partial<FiltrosDeUsuarios>} cambios cualquier cambio que no sea de página vuelve a la primera
   * @returns {void}
   */
  function cambiar(cambios) {
    enrutador.push({ name: 'usuarios', query: aConsultaDeRuta(conCambios(filtros.value, cambios)) })
  }

  return { filtros, lista, conteos, roles: useRoles(), cambiar }
}

/**
 * Altas y cambios de usuarios (RF-F42). Tras cada cambio se vuelve a pedir lo afectado; si la persona se
 * editó a sí misma, también su perfil.
 */
export function useEdicionDeUsuarios() {
  const queryClient = useQueryClient()
  const sesion = useSesion()
  const notificaciones = useNotificaciones()

  /** @param {number} id */
  async function trasCambiar(id) {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.usuarios() }),
      queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.personas() }),
      queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.clientes() }),
      queryClient.invalidateQueries({ queryKey: clavesDeIdentidad.roles() }),
      id === sesion.perfil?.id ? sesion.refrescarPerfil() : Promise.resolve(),
    ])
  }

  const guardado = useMutation({
    /**
     * @param {{ original: Usuario | null, datos: { nombre: string, correo: string, rolId: number, clienteId: number | null, contrasena: string, activo: boolean } }} pedido
     * @returns {Promise<Usuario>}
     */
    mutationFn: async ({ original, datos }) => {
      if (!original) {
        return crearUsuario(datos)
      }
      // Solo viaja lo cambiado; el rol tiene su propio endpoint.
      /** @type {Parameters<typeof modificarUsuario>[1]} */
      const cambios = {}
      if (datos.nombre !== original.nombre) cambios.nombre = datos.nombre
      if (datos.correo !== original.correo) cambios.correo = datos.correo
      if (datos.clienteId !== (original.cliente?.id ?? null)) cambios.clienteId = datos.clienteId
      if (datos.activo !== original.activo) cambios.activo = datos.activo
      let usuario = Object.keys(cambios).length ? await modificarUsuario(original.id, cambios) : original
      if (datos.rolId !== original.rol.id) {
        usuario = await asignarRol(original.id, datos.rolId)
      }
      return usuario
    },
    onSuccess: async (usuario, { original }) => {
      notificaciones.exito(original ? 'Usuario actualizado' : 'Usuario creado', usuario.nombre)
      await trasCambiar(usuario.id)
    },
    // Si falló el cambio de rol después de guardar los datos, la lista debe mostrar lo que sí quedó.
    onError: async (_causa, { original }) => original && (await trasCambiar(original.id)),
  })

  const activacion = useMutation({
    /** @param {{ usuario: Usuario, activo: boolean }} pedido */
    mutationFn: ({ usuario, activo }) => modificarUsuario(usuario.id, { activo }),
    onSuccess: async (usuario) => {
      notificaciones.exito(
        usuario.activo ? 'Usuario reactivado' : 'Usuario desactivado',
        usuario.activo
          ? `${usuario.nombre} puede volver a iniciar sesión.`
          : `Se cerraron las sesiones de ${usuario.nombre}.`,
      )
      await trasCambiar(usuario.id)
    },
  })

  const restablecimiento = useMutation({
    /** @param {{ usuario: Usuario, contrasena: string }} pedido */
    mutationFn: ({ usuario, contrasena }) => modificarUsuario(usuario.id, { contrasena }),
    onSuccess: (usuario) =>
      notificaciones.exito('Contraseña restablecida', `Se cerraron las sesiones de ${usuario.nombre}.`),
  })

  /**
   * Desactiva o reactiva; si la API lo impide (el último administrador activo), lo avisa.
   * @param {Usuario} usuario
   * @returns {Promise<void>}
   */
  async function alternarActivo(usuario) {
    await activacion
      .mutateAsync({ usuario, activo: !usuario.activo })
      .catch((causa) =>
        notificaciones.error(
          `No se pudo ${usuario.activo ? 'desactivar' : 'reactivar'}`,
          mensajeDeError(causa, ['activo']),
        ),
      )
  }

  /**
   * @param {Usuario} usuario
   * @param {string} contrasena
   * @returns {Promise<string>} el mensaje de error para el campo; '' si salió bien
   */
  function restablecer(usuario, contrasena) {
    return restablecimiento.mutateAsync({ usuario, contrasena }).then(
      () => '',
      (causa) => mensajeDeError(causa, ['password']),
    )
  }

  return { guardado, alternarActivo, restablecer, restableciendo: computed(() => restablecimiento.isPending.value) }
}
