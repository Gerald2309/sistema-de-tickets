import { useMutation, useQuery } from '@tanstack/vue-query'
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { aIsoConDesfase } from '@/compartido/presentacion/fechas.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { actualizarIncidente, crearIncidente, obtenerCatalogos } from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import { partesEditables } from '../permisos.js'
import { usePestanas } from '../tienda/pestanas.js'
import { consultaDeDetalle } from './useIncidente.js'
import { useInvalidacion } from './useInvalidacion.js'

export const MAXIMO_TITULO = 255
export const MAXIMO_DESCRIPCION = 10_000

/** Campos del formulario y la parte del permiso que exige cada uno (API 8, permiso por campo). */
const PARTE_DE_CAMPO = /** @type {const} */ ({
  titulo: 'contenido',
  descripcion: 'contenido',
  prioridad: 'contenido',
  categoria: 'contenido',
  vencimiento: 'contenido',
  asignadoId: 'asignacion',
})

/** @typedef {keyof typeof PARTE_DE_CAMPO} Campo */
/** @typedef {{ titulo: string, descripcion: string, prioridad: string, categoria: string, asignadoId: number | null, vencimiento: Date | null }} Campos */

/** @returns {Campos} */
const camposIniciales = () => ({
  titulo: '',
  descripcion: '',
  prioridad: 'media',
  categoria: 'otros',
  asignadoId: null,
  vencimiento: null,
})

/**
 * @param {Campos} campos
 * @param {boolean} creando en la creación el vencimiento debe ser futuro
 * @returns {Partial<Record<Campo, string>>} el primer error de cada campo
 */
export function validarIncidente(campos, creando) {
  /** @type {Partial<Record<Campo, string>>} */
  const errores = {}
  if (!campos.titulo.trim()) {
    errores.titulo = 'Escriba un título.'
  } else if (campos.titulo.length > MAXIMO_TITULO) {
    errores.titulo = `Use como máximo ${MAXIMO_TITULO} caracteres.`
  }
  if (!campos.descripcion.trim()) {
    errores.descripcion = 'Describa qué ocurre.'
  } else if (campos.descripcion.length > MAXIMO_DESCRIPCION) {
    errores.descripcion = `Use como máximo ${MAXIMO_DESCRIPCION.toLocaleString('es-CO')} caracteres.`
  }
  if (!campos.vencimiento) {
    errores.vencimiento = 'Elija la fecha de vencimiento.'
  } else if (creando && campos.vencimiento.getTime() <= Date.now()) {
    errores.vencimiento = 'La fecha de vencimiento debe ser futura.'
  }
  return errores
}

/**
 * Crear y editar incidentes (RF-F35 a RF-F40). En la edición solo se habilitan los campos que el permiso
 * permite y solo se envían los cambiados; los errores 422 quedan bajo su campo.
 * @param {() => number | undefined} idActual sin id, se crea
 */
export function useFormularioIncidente(idActual) {
  const sesion = useSesion()
  const pestanas = usePestanas()
  const notificaciones = useNotificaciones()
  const enrutador = useRouter()
  const { trasCambiarIncidentes } = useInvalidacion()

  const id = computed(idActual)
  const creando = computed(() => id.value === undefined)

  const catalogos = useQuery({
    queryKey: claves.catalogos,
    queryFn: ({ signal }) => obtenerCatalogos(signal),
    staleTime: Infinity,
  })
  const original = useQuery({
    ...consultaDeDetalle(id.value ?? 0),
    enabled: computed(() => !creando.value),
  })

  const campos = reactive(camposIniciales())
  const inicial = ref(JSON.stringify(campos))
  const cargado = ref(creando.value)

  // Al llegar el incidente se copian sus valores una sola vez: un refresco posterior no pisa lo escrito.
  watch(
    () => original.data.value,
    (incidente) => {
      if (!incidente || cargado.value) {
        return
      }
      Object.assign(campos, {
        titulo: incidente.titulo,
        descripcion: incidente.descripcion,
        prioridad: incidente.prioridad,
        categoria: incidente.categoria,
        asignadoId: incidente.asignado?.id ?? null,
        vencimiento: new Date(incidente.fechaVencimiento),
      })
      inicial.value = JSON.stringify(campos)
      cargado.value = true
    },
    { immediate: true },
  )

  const partes = computed(() =>
    creando.value
      ? { contenido: true, estado: false, asignacion: sesion.tiene('reasignar') }
      : original.data.value
        ? partesEditables(sesion, original.data.value)
        : { contenido: false, estado: false, asignacion: false },
  )
  /** @param {Campo} campo */
  const habilitado = (campo) => partes.value[PARTE_DE_CAMPO[campo]]

  const modificado = computed(() => cargado.value && JSON.stringify(campos) !== inicial.value)
  watch(modificado, (valor) => id.value !== undefined && pestanas.marcarSinGuardar(id.value, valor))

  // Errores: los del cliente aparecen al salir del campo o al enviar; los del servidor, tras enviar.
  const servidor = useErroresDeFormulario({
    titulo: 'titulo',
    descripcion: 'descripcion',
    prioridad: 'prioridad',
    categoria: 'categoria',
    usuario_asignado_id: 'asignadoId',
    fecha_vencimiento: 'vencimiento',
  })
  const tocados = reactive(new Set(/** @type {Campo[]} */ ([])))
  const deCliente = computed(() => validarIncidente(campos, creando.value))
  /**
   * @param {Campo} campo
   * @returns {string[]}
   */
  const erroresDe = (campo) => {
    const local = tocados.has(campo) && habilitado(campo) ? deCliente.value[campo] : undefined
    return local ? [local] : (servidor.errores[campo] ?? [])
  }
  /** @param {Campo} campo */
  const tocar = (campo) => tocados.add(campo)
  /** @param {Campo} campo */
  const alEscribir = (campo) => servidor.limpiarCampo(campo)

  /** @returns {Record<string, unknown>} lo que viaja a la API */
  function cuerpo() {
    const valores = { ...campos, fechaVencimiento: campos.vencimiento ? aIsoConDesfase(campos.vencimiento) : undefined }
    const antes = JSON.parse(inicial.value)
    /** @type {Record<string, unknown>} */
    const salida = {}
    for (const campo of /** @type {Campo[]} */ (Object.keys(PARTE_DE_CAMPO))) {
      const cambiado = JSON.stringify(campos[campo]) !== JSON.stringify(antes[campo])
      if (
        !habilitado(campo) ||
        (!creando.value && !cambiado) ||
        (creando.value && campo === 'asignadoId' && campos.asignadoId === null)
      ) {
        continue
      }
      const clave = campo === 'vencimiento' ? 'fechaVencimiento' : campo
      salida[clave] = /** @type {Record<string, unknown>} */ (valores)[clave]
    }
    return salida
  }

  const guardado = useMutation({
    /** @param {Record<string, unknown>} cambios */
    mutationFn: (cambios) =>
      creando.value ? crearIncidente(cambios) : actualizarIncidente(/** @type {number} */ (id.value), cambios),
  })

  /**
   * @returns {Promise<Campo | null>} el primer campo con error, para que la vista lo enfoque; null si se guardó
   */
  async function guardar() {
    Object.keys(PARTE_DE_CAMPO).forEach((campo) => tocar(/** @type {Campo} */ (campo)))
    const conError = /** @type {Campo[]} */ (Object.keys(PARTE_DE_CAMPO)).find((campo) => erroresDe(campo).length > 0)
    if (conError) {
      return conError
    }
    const cambios = cuerpo()
    if (!creando.value && Object.keys(cambios).length === 0) {
      await enrutador.push({ name: 'incidente', params: { id: id.value } })
      return null
    }
    servidor.limpiar()
    try {
      const incidente = await guardado.mutateAsync(cambios)
      inicial.value = JSON.stringify(campos)
      notificaciones.exito(
        creando.value ? `${incidente.codigo} creado` : 'Cambios guardados',
        creando.value ? incidente.titulo : incidente.codigo,
      )
      await trasCambiarIncidentes([incidente.id])
      await enrutador.push({ name: 'incidente', params: { id: incidente.id } })
      return null
    } catch (causa) {
      servidor.aplicar(causa)
      return (
        /** @type {Campo[]} */ (Object.keys(PARTE_DE_CAMPO)).find(
          (campo) => (servidor.errores[campo] ?? []).length > 0,
        ) ?? null
      )
    }
  }

  /** @returns {void} la persona aceptó perder lo escrito: salir ya no vuelve a preguntar */
  function descartar() {
    inicial.value = JSON.stringify(campos)
  }

  return {
    creando,
    catalogos,
    original,
    cargado,
    campos,
    partes,
    habilitado,
    modificado,
    erroresDe,
    errorGeneral: servidor.general,
    tocar,
    alEscribir,
    guardar,
    guardando: computed(() => guardado.isPending.value),
    descartar,
  }
}
