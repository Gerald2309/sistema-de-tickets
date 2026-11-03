import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, ref, toRaw, watch } from 'vue'
import { aErrorDeApi } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { guardarAutomatizacion, probarPasos, verAutomatizacion } from '../api/automatizaciones.js'
import { clavesDeAutomatizaciones } from '../api/claves.js'
import { disparoInicial } from '../catalogo.js'

/** @typedef {import('../tipos.js').Disparo} Disparo */
/** @typedef {import('../tipos.js').Paso} Paso */

/**
 * @typedef {object} Borrador lo que se edita; se envía completo al guardar
 * @property {string} nombre
 * @property {boolean} activa
 * @property {Disparo} disparo
 * @property {Paso[]} pasos
 */

/** @returns {Borrador} */
const borradorNuevo = () => ({
  nombre: 'Nueva automatización',
  activa: false,
  disparo: disparoInicial('creacion'),
  pasos: [],
})

/**
 * Editar o crear una automatización: el borrador, guardarlo y probarlo. Los errores de la API llegan por la
 * ruta del dato (`pasos.0.si.1.usuario_id`) para mostrarlos en su bloque.
 * @param {() => number | null} id null para una nueva
 */
export function useEditorDeAutomatizacion(id) {
  const queryClient = useQueryClient()
  const notificaciones = useNotificaciones()
  const borrador = reactive(borradorNuevo())
  const guardadoComo = ref(JSON.stringify(borrador))
  const errores = ref(/** @type {Record<string, string[]>} */ ({}))

  const carga = useQuery({
    queryKey: computed(() => clavesDeAutomatizaciones.una(id() ?? 0)),
    queryFn: ({ signal }) => verAutomatizacion(/** @type {number} */ (id()), signal),
    enabled: computed(() => id() !== null),
  })

  watch(
    carga.data,
    (automatizacion) => {
      if (automatizacion) {
        const { nombre, activa, disparo, pasos } = structuredClone(toRaw(automatizacion))
        Object.assign(borrador, { nombre, activa, disparo, pasos })
        guardadoComo.value = JSON.stringify(borrador)
      }
    },
    { immediate: true },
  )

  /**
   * @param {unknown} causa
   * @param {string} titulo
   */
  function alFallar(causa, titulo) {
    const problema = aErrorDeApi(causa)
    errores.value = problema.errores
    notificaciones.error(titulo, problema.esValidacion ? 'Revise los bloques marcados.' : problema.detalle)
  }

  const guardado = useMutation({
    mutationFn: () => guardarAutomatizacion({ id: id() ?? undefined, ...structuredClone(toRaw(borrador)) }),
    onSuccess: async (automatizacion) => {
      errores.value = {}
      guardadoComo.value = JSON.stringify(borrador)
      notificaciones.exito('Automatización guardada', automatizacion.nombre)
      queryClient.setQueryData(clavesDeAutomatizaciones.una(automatizacion.id), automatizacion)
      await queryClient.invalidateQueries({ queryKey: clavesDeAutomatizaciones.todas(), exact: true })
    },
    onError: (causa) => alFallar(causa, 'No se pudo guardar'),
  })

  const prueba = useMutation({
    mutationFn: () => probarPasos(structuredClone(toRaw(borrador.pasos))),
    onSuccess: () => (errores.value = {}),
    onError: (causa) => alFallar(causa, 'No se pudo probar'),
  })

  return {
    borrador,
    carga,
    errores,
    /** Si hay cambios sin guardar. */
    cambiado: computed(() => JSON.stringify(borrador) !== guardadoComo.value),
    /** @returns {Promise<import('../tipos.js').Automatizacion | null>} null si no se pudo (ya se avisó) */
    guardar: () => guardado.mutateAsync().catch(() => null),
    guardando: guardado.isPending,
    probar: () => prueba.mutate(),
    probando: prueba.isPending,
    resultado: prueba.data,
    /** Cualquier cambio vuelve viejo el resultado de una prueba y los errores mostrados. */
    olvidarRevision: () => {
      prueba.reset()
      errores.value = {}
    },
  }
}
