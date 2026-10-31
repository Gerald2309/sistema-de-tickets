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
const UN_DIA_MS = 24 * 60 * 60 * 1000

/** La acción con la que la API registra una alerta de seguridad. */
export const ALERTA = 'alerta_de_seguridad'

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

/**
 * Alertas de seguridad desde ayer (inicios fallidos por encima del umbral), para avisarlas al entrar. Solo
 * hace falta el total: pide una fila.
 */
export function useAlertasRecientes() {
  const ayer = new Date(Date.now() - UN_DIA_MS)
  const dos = (/** @type {number} */ n) => String(n).padStart(2, '0')
  const desde = `${ayer.getFullYear()}-${dos(ayer.getMonth() + 1)}-${dos(ayer.getDate())}`
  return useQuery({
    queryKey: ['auditoria', 'alertas-recientes', desde],
    queryFn: async ({ signal }) => (await listarAuditoria({ accion: ALERTA, desde }, signal, 1)).total,
  })
}
