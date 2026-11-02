import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, reactive, ref, watch } from 'vue'
import { mensajeDeError } from '@/compartido/api/errores.js'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { useSesion } from '@/modulos/identidad/index.js'
import { escribirMensaje, marcarConversacionLeida, obtenerConversacion } from '../api/incidentes.js'
import { claves } from '../api/claves.js'
import { LIMITES_DE_MENSAJE, revisarImagenes } from '../conversacion.js'
import { puedeConversar, puedeEscribirNotaInterna } from '../permisos.js'

/**
 * Conversación de un incidente: los mensajes, el borrador y su envío. Los mensajes de la otra persona llegan
 * por el aviso de tiempo real, que invalida todo lo del incidente (modelo-datos-cliente §4.3). Verla en
 * pantalla la deja leída: si había mensajes sin leer, se marca y se refrescan el detalle y los listados.
 * @param {() => import('../tipos.js').Incidente} incidenteActual
 */
export function useConversacion(incidenteActual) {
  const sesion = useSesion()
  const notificaciones = useNotificaciones()
  const queryClient = useQueryClient()

  const incidente = computed(incidenteActual)
  const id = computed(() => incidente.value.id)

  // Por tramos, como un chat: primero lo último; «Ver mensajes anteriores» pide el tramo de antes.
  const mensajes = useInfiniteQuery({
    queryKey: computed(() => claves.mensajes(id.value)),
    queryFn: ({ pageParam, signal }) => obtenerConversacion(id.value, pageParam, signal),
    initialPageParam: /** @type {string | null} */ (null),
    getNextPageParam: (tramo) => tramo.siguiente,
  })
  /** En orden de lectura: el más antiguo cargado arriba, el último abajo. */
  const enOrden = computed(() => (mensajes.data.value?.pages.flatMap((tramo) => tramo.elementos) ?? []).reverse())

  watch(
    () => mensajes.data.value !== undefined && incidente.value.mensajesSinLeer > 0,
    async (hayQueMarcar) => {
      if (!hayQueMarcar) {
        return
      }
      // Si falla, el contador sigue y se reintenta la próxima vez: no merece interrumpir a la persona.
      await marcarConversacionLeida(id.value).catch(() => {})
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: claves.detalle(id.value), exact: true }),
        queryClient.invalidateQueries({ queryKey: claves.listas() }),
      ])
    },
    { immediate: true },
  )

  const permisos = computed(() => ({
    escribir: puedeConversar(sesion, incidente.value),
    notaInterna: puedeEscribirNotaInterna(sesion, incidente.value),
  }))

  const borrador = reactive({ cuerpo: '', interno: false, imagenes: /** @type {File[]} */ ([]) })
  /** Por qué no se adjuntaron algunas imágenes; se muestra bajo el campo. */
  const rechazos = ref(/** @type {string[]} */ ([]))

  /** @param {File[]} archivos */
  function adjuntar(archivos) {
    const { aceptadas, rechazos: motivos } = revisarImagenes(borrador.imagenes.length, archivos)
    borrador.imagenes.push(...aceptadas)
    rechazos.value = motivos
  }

  /** @param {number} indice */
  function quitar(indice) {
    borrador.imagenes.splice(indice, 1)
    rechazos.value = []
  }

  // Una clave por borrador: reintentar el mismo envío no lo duplica; si el borrador cambia, es otro mensaje.
  let claveDelBorrador = crypto.randomUUID()
  watch(borrador, () => (claveDelBorrador = crypto.randomUUID()), { deep: true })

  const listo = computed(
    () =>
      (borrador.cuerpo.trim() !== '' || borrador.imagenes.length > 0) &&
      borrador.cuerpo.length <= LIMITES_DE_MENSAJE.caracteres,
  )

  const envio = useMutation({
    mutationFn: () =>
      escribirMensaje(
        id.value,
        {
          cuerpo: borrador.cuerpo,
          interno: borrador.interno && permisos.value.notaInterna,
          imagenes: [...borrador.imagenes],
        },
        claveDelBorrador,
      ),
    onSuccess: async () => {
      Object.assign(borrador, { cuerpo: '', interno: false, imagenes: [] })
      rechazos.value = []
      await queryClient.invalidateQueries({ queryKey: claves.mensajes(id.value) })
    },
    // El borrador se conserva para corregirlo y reintentar.
    onError: (causa) => notificaciones.error('No se envió el mensaje', mensajeDeError(causa)),
  })

  /** @returns {Promise<void>} */
  async function enviar() {
    if (listo.value && !envio.isPending.value) {
      await envio.mutateAsync().catch(() => {})
    }
  }

  return {
    mensajes,
    enOrden,
    hayAnteriores: computed(() => mensajes.hasNextPage.value),
    cargandoAnteriores: computed(() => mensajes.isFetchingNextPage.value),
    verAnteriores: () => mensajes.fetchNextPage(),
    permisos,
    borrador,
    rechazos,
    adjuntar,
    quitar,
    listo,
    enviar,
    enviando: computed(() => envio.isPending.value),
  }
}
