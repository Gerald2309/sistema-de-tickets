import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

const CLAVE_ALMACEN = 'mesa-de-ayuda.pestanas'
export const MAXIMO_DE_PESTANAS = 8

/**
 * Pestaña de un ticket. Recuerda en qué pantalla quedó (detalle o edición), como una pestaña del navegador.
 * @typedef {object} Pestana
 * @property {number} id
 * @property {string | null} codigo null hasta que llega el incidente
 * @property {string} titulo
 * @property {string | null} estado para el punto de color
 * @property {'detalle' | 'edicion'} pantalla
 * @property {boolean} sinGuardar tiene un formulario modificado
 */

/** @returns {Pestana[]} */
function leerGuardadas() {
  try {
    const guardadas = JSON.parse(sessionStorage.getItem(CLAVE_ALMACEN) ?? '[]')
    return Array.isArray(guardadas)
      ? guardadas
          .filter((p) => Number.isInteger(p?.id) && p.id > 0)
          .slice(0, MAXIMO_DE_PESTANAS)
          .map((p) => ({
            id: p.id,
            codigo: typeof p.codigo === 'string' ? p.codigo : null,
            titulo: typeof p.titulo === 'string' ? p.titulo : '',
            estado: typeof p.estado === 'string' ? p.estado : null,
            pantalla: 'detalle',
            sinGuardar: false,
          }))
      : []
  } catch {
    return []
  }
}

/**
 * Pestañas de tickets (RF-F11, F-09). Estado de interfaz: sobrevive a recargar (sessionStorage) y se olvida al
 * cerrar sesión. Solo se guardan id, código, título y estado; el contenido se pide siempre a la API.
 */
export const usePestanas = defineStore('pestanas', () => {
  const lista = ref(leerGuardadas())
  const activa = ref(/** @type {number | null} */ (null))

  watch(
    lista,
    (pestanas) => {
      try {
        const guardables = pestanas.map(({ id, codigo, titulo, estado }) => ({ id, codigo, titulo, estado }))
        sessionStorage.setItem(CLAVE_ALMACEN, JSON.stringify(guardables))
      } catch {
        // Sin almacenamiento, las pestañas viven solo en memoria.
      }
    },
    { deep: true },
  )

  /** @param {number} id */
  const buscar = (id) => lista.value.find((pestana) => pestana.id === id)

  /**
   * Abre la pestaña del incidente o activa la existente. Con 8 abiertas cierra la más antigua sin cambios sin
   * guardar; si todas los tienen, no abre.
   * @param {number} id
   * @param {Pestana['pantalla']} [pantalla]
   * @returns {boolean} se abrió o ya estaba
   */
  function abrir(id, pantalla = 'detalle') {
    const existente = buscar(id)
    if (existente) {
      existente.pantalla = pantalla
      activa.value = id
      return true
    }
    if (lista.value.length >= MAXIMO_DE_PESTANAS) {
      const indice = lista.value.findIndex((pestana) => !pestana.sinGuardar)
      if (indice === -1) {
        return false
      }
      lista.value.splice(indice, 1)
    }
    lista.value.push({ id, codigo: null, titulo: '', estado: null, pantalla, sinGuardar: false })
    activa.value = id
    return true
  }

  /**
   * Cierra la pestaña y dice cuál activar después: la de la derecha, si no la de la izquierda (como el
   * navegador). null significa volver al listado.
   * @param {number} id
   * @returns {number | null}
   */
  function cerrar(id) {
    const indice = lista.value.findIndex((pestana) => pestana.id === id)
    if (indice === -1) {
      return null
    }
    lista.value.splice(indice, 1)
    if (activa.value !== id) {
      return activa.value
    }
    activa.value = null
    return (lista.value[indice] ?? lista.value[indice - 1])?.id ?? null
  }

  /**
   * @param {number} id
   * @param {{ codigo: string, titulo: string, estado: string }} datos
   * @returns {void}
   */
  function describir(id, { codigo, titulo, estado }) {
    const pestana = buscar(id)
    if (pestana) {
      Object.assign(pestana, { codigo, titulo, estado })
    }
  }

  /**
   * @param {number} id
   * @param {boolean} valor
   * @returns {void}
   */
  function marcarSinGuardar(id, valor) {
    const pestana = buscar(id)
    if (pestana) {
      pestana.sinGuardar = valor
    }
  }

  /** @returns {void} ninguna pestaña activa: se está en otra pantalla */
  function desactivar() {
    activa.value = null
  }

  /** @returns {void} al cerrar sesión */
  function olvidar() {
    lista.value = []
    activa.value = null
    try {
      sessionStorage.removeItem(CLAVE_ALMACEN)
    } catch {
      // Nada que borrar.
    }
  }

  return {
    lista,
    activa,
    hayCambiosSinGuardar: computed(() => lista.value.some((pestana) => pestana.sinGuardar)),
    buscar,
    abrir,
    cerrar,
    describir,
    marcarSinGuardar,
    desactivar,
    olvidar,
  }
})
