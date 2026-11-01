import { useMutation, useQuery } from '@tanstack/vue-query'
import { computed, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { aIsoConDesfase } from '@/compartido/presentacion/fechas.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { crearIncidente, obtenerCatalogos, obtenerPlazoPropuesto } from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import { useInvalidacion } from './useInvalidacion.js'

export const MAXIMO_TITULO = 255
export const MAXIMO_DESCRIPCION = 10_000

/** @typedef {'titulo' | 'descripcion' | 'prioridad' | 'categoria' | 'vencimiento' | 'asignadoId'} Campo */
/** @type {Campo[]} */
const CAMPOS = ['titulo', 'descripcion', 'prioridad', 'categoria', 'vencimiento', 'asignadoId']
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
 * @returns {Partial<Record<Campo, string>>} el primer error de cada campo
 */
export function validarIncidente(campos) {
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
  } else if (campos.vencimiento.getTime() <= Date.now()) {
    errores.vencimiento = 'La fecha de vencimiento debe ser futura.'
  }
  return errores
}

/**
 * Crear incidentes (RF-F35 a RF-F40). El título y la descripción quedan fijos al crearlo; lo demás se ajusta
 * después desde el detalle. Los errores 422 quedan bajo su campo.
 */
export function useFormularioIncidente() {
  const sesion = useSesion()
  const notificaciones = useNotificaciones()
  const enrutador = useRouter()
  const { trasCambiarIncidentes } = useInvalidacion()

  const catalogos = useQuery({
    queryKey: claves.catalogos,
    queryFn: ({ signal }) => obtenerCatalogos(signal),
    staleTime: Infinity,
  })

  const campos = reactive(camposIniciales())
  // La fecha la propone el acuerdo de servicio de la empresa hasta que la persona la cambie; mientras tanto, ni
  // cuenta como cambio sin guardar ni viaja a la API, que la calcula al crear.
  const vencimientoEditado = ref(false)
  const instantanea = () =>
    JSON.stringify({ ...campos, vencimiento: vencimientoEditado.value ? campos.vencimiento : null })
  const inicial = ref(instantanea())

  const plazoPropuesto = useQuery({
    queryKey: computed(() => claves.plazoPropuesto(campos.prioridad)),
    queryFn: ({ signal }) => obtenerPlazoPropuesto(campos.prioridad, signal),
  })
  watch(plazoPropuesto.data, (propuesto) => {
    if (propuesto && !vencimientoEditado.value) {
      campos.vencimiento = new Date(propuesto.fechaVencimiento)
    }
  })
  // Elegir a quién se asigna un incidente nuevo es repartir trabajo: solo quien reasigna.
  const puedeAsignar = computed(() => sesion.tiene('reasignar'))
  const modificado = computed(() => instantanea() !== inicial.value)

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
  const deCliente = computed(() => validarIncidente(campos))
  /**
   * @param {Campo} campo
   * @returns {string[]}
   */
  const erroresDe = (campo) => {
    const local = tocados.has(campo) ? deCliente.value[campo] : undefined
    return local ? [local] : (servidor.errores[campo] ?? [])
  }
  /** @param {Campo} campo */
  const tocar = (campo) => tocados.add(campo)
  /** @param {Campo} campo */
  const alEscribir = (campo) => {
    servidor.limpiarCampo(campo)
    vencimientoEditado.value ||= campo === 'vencimiento'
  }

  /** @returns {Record<string, unknown>} lo que viaja a la API */
  function cuerpo() {
    const { vencimiento, asignadoId, ...resto } = campos
    return {
      ...resto,
      ...(vencimientoEditado.value && vencimiento ? { fechaVencimiento: aIsoConDesfase(vencimiento) } : {}),
      ...(puedeAsignar.value && asignadoId !== null ? { asignadoId } : {}),
    }
  }

  // Una clave por formulario: si el envío se repite (doble clic, red que se corta), la API no lo duplica.
  const claveDeIdempotencia = crypto.randomUUID()
  const guardado = useMutation({
    /** @param {Record<string, unknown>} datos */
    mutationFn: (datos) => crearIncidente(datos, claveDeIdempotencia),
  })

  /**
   * @returns {Promise<Campo | null>} el primer campo con error, para que la vista lo enfoque; null si se guardó
   */
  async function guardar() {
    CAMPOS.forEach(tocar)
    const conError = CAMPOS.find((campo) => erroresDe(campo).length > 0)
    if (conError) {
      return conError
    }
    servidor.limpiar()
    try {
      const incidente = await guardado.mutateAsync(cuerpo())
      inicial.value = instantanea()
      notificaciones.exito(`${incidente.codigo} creado`, incidente.titulo)
      await trasCambiarIncidentes([incidente.id])
      await enrutador.push({ name: 'incidente', params: { id: incidente.id } })
      return null
    } catch (causa) {
      servidor.aplicar(causa)
      return CAMPOS.find((campo) => (servidor.errores[campo] ?? []).length > 0) ?? null
    }
  }

  /** @returns {void} la persona aceptó perder lo escrito: salir ya no vuelve a preguntar */
  function descartar() {
    inicial.value = instantanea()
  }

  return {
    catalogos,
    campos,
    plazoPropuesto,
    puedeAsignar,
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
