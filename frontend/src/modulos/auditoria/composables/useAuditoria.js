import { keepPreviousData, useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  aConsultaDeRuta,
  conCambios,
  enteroPositivo,
  sinIndefinidos,
  textoDeConsulta,
} from '@/compartido/utilidades/consulta.js'
import { listarAuditoria } from '../api/auditoria.js'
import { ACCIONES } from '../presentacion.js'

/** @typedef {import('../api/auditoria.js').FiltrosDeAuditoria} FiltrosDeAuditoria */

const FECHA = /^\d{4}-\d{2}-\d{2}$/

/**
 * Filtros de la auditoría ↔ dirección. Lo desconocido se descarta; el servidor valida el resto.
 * @param {import('vue-router').LocationQuery} consulta
 * @returns {FiltrosDeAuditoria}
 */
export function filtrosDeAuditoria(consulta) {
  const accion = textoDeConsulta(consulta.accion)
  const desde = textoDeConsulta(consulta.desde)
  const hasta = textoDeConsulta(consulta.hasta)
  return sinIndefinidos({
    accion: accion && accion in ACCIONES ? accion : undefined,
    usuario: enteroPositivo(consulta.usuario),
    afectado: enteroPositivo(consulta.afectado),
    desde: desde && FECHA.test(desde) ? desde : undefined,
    hasta: hasta && FECHA.test(hasta) ? hasta : undefined,
    pagina: enteroPositivo(consulta.pagina),
  })
}

/** Registro de auditoría (RF-F44): filtros en la dirección, paginado en el servidor, solo lectura. */
export function useAuditoria() {
  const ruta = useRoute()
  const enrutador = useRouter()
  const filtros = computed(() => filtrosDeAuditoria(ruta.name === 'auditoria' ? ruta.query : {}))

  const lista = useQuery({
    queryKey: computed(() => ['auditoria', filtros.value]),
    queryFn: ({ signal }) => listarAuditoria(filtros.value, signal),
    placeholderData: keepPreviousData,
  })

  /**
   * @param {Partial<FiltrosDeAuditoria>} cambios cualquier cambio que no sea de página vuelve a la primera
   * @returns {void}
   */
  function cambiar(cambios) {
    enrutador.push({ name: 'auditoria', query: aConsultaDeRuta(conCambios(filtros.value, cambios)) })
  }

  return { filtros, lista, cambiar }
}
