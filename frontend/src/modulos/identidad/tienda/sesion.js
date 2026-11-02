import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as api from '../api/sesion.js'

/** @typedef {import('../tipos.js').Perfil} Perfil */
/** @typedef {import('../tipos.js').Permiso} Permiso */

const CLAVE_ALMACEN = 'mesa-de-ayuda.sesion'

/**
 * Motivo del último cierre de sesión, para el mensaje del inicio de sesión.
 * @typedef {'cerrada' | 'expirada' | null} MotivoDeSalida
 */

/**
 * Lee el token guardado. `sessionStorage` puede no estar disponible (modo privado estricto): sin él, la
 * sesión vive solo en memoria y recargar pide iniciar sesión otra vez.
 * @returns {{ token: string, expiraEn: string | null } | null}
 */
function leerGuardada() {
  try {
    const guardada = JSON.parse(sessionStorage.getItem(CLAVE_ALMACEN) ?? 'null')
    return guardada && typeof guardada.token === 'string' ? guardada : null
  } catch {
    return null
  }
}

/**
 * @param {{ token: string, expiraEn: string | null } | null} credencial
 * @returns {void}
 */
function guardar(credencial) {
  try {
    if (credencial) {
      sessionStorage.setItem(CLAVE_ALMACEN, JSON.stringify(credencial))
    } else {
      sessionStorage.removeItem(CLAVE_ALMACEN)
    }
  } catch {
    // Sin almacenamiento disponible la sesión sigue en memoria.
  }
}

/**
 * Sesión de la persona (modelo-datos-cliente.md §7). Es el único lugar que lee o escribe el token. Guarda en el
 * navegador solo el token y su expiración (F-05); el perfil y los permisos se piden siempre a `GET /me`.
 */
export const useSesion = defineStore('sesion', () => {
  const guardada = leerGuardada()
  const token = ref(/** @type {string | null} */ (guardada?.token ?? null))
  const expiraEn = ref(/** @type {string | null} */ (guardada?.expiraEn ?? null))
  const perfil = ref(/** @type {Perfil | null} */ (null))
  const motivoDeSalida = ref(/** @type {MotivoDeSalida} */ (null))

  /** @type {ReturnType<typeof setTimeout> | undefined} */
  let temporizador

  const permisos = computed(() => new Set(perfil.value?.permisos ?? []))
  const autenticada = computed(() => token.value !== null && perfil.value !== null)
  const debeCambiarContrasena = computed(() => perfil.value?.debeCambiarContrasena ?? false)

  /**
   * @param {Permiso} permiso
   * @returns {boolean}
   */
  function tiene(permiso) {
    return permisos.value.has(permiso)
  }

  /**
   * @param {...Permiso} lista
   * @returns {boolean} tiene al menos uno de los permisos
   */
  function tieneAlguno(...lista) {
    return lista.some((permiso) => permisos.value.has(permiso))
  }

  // Cierra la sesión a la hora exacta de su expiración, en vez de esperar al siguiente 401.
  function programarExpiracion() {
    clearTimeout(temporizador)
    if (!expiraEn.value) {
      return
    }
    const restante = new Date(expiraEn.value).getTime() - Date.now()
    if (restante <= 0) {
      expirar()
      return
    }
    // setTimeout admite como mucho unos 24,8 días; el token dura horas.
    temporizador = setTimeout(expirar, Math.min(restante, 2 ** 31 - 1))
  }

  function limpiar() {
    clearTimeout(temporizador)
    token.value = null
    expiraEn.value = null
    perfil.value = null
    guardar(null)
  }

  /**
   * Recupera la sesión guardada al arrancar. Devuelve false si no hay sesión o ya no es válida.
   * @returns {Promise<boolean>}
   */
  async function restaurar() {
    if (!token.value) {
      return false
    }
    try {
      perfil.value = await api.obtenerPerfil()
      programarExpiracion()
      return true
    } catch {
      limpiar()
      return false
    }
  }

  /**
   * @param {string} correo
   * @param {string} contrasena
   * @returns {Promise<void>}
   */
  async function iniciar(correo, contrasena) {
    const credencial = await api.iniciarSesion(correo, contrasena)
    token.value = credencial.token
    expiraEn.value = credencial.expiraEn
    guardar(credencial)
    try {
      perfil.value = await api.obtenerPerfil()
    } catch (error) {
      limpiar()
      throw error
    }
    motivoDeSalida.value = null
    programarExpiracion()
  }

  /**
   * Cierra la sesión. La sesión local se borra aunque el servidor no responda (RF-F02).
   * @returns {Promise<void>}
   */
  async function cerrar() {
    if (!token.value) {
      return
    }
    await api.cerrarSesion().catch(() => {})
    limpiar()
    motivoDeSalida.value = 'cerrada'
  }

  // El token venció o fue revocado (RF-F03). Varias peticiones pueden fallar a la vez: solo actúa la primera.
  function expirar() {
    if (!token.value) {
      return
    }
    limpiar()
    motivoDeSalida.value = 'expirada'
  }

  /** @returns {Promise<void>} vuelve a leer el perfil (por ejemplo, tras cambiar algo propio) */
  async function refrescarPerfil() {
    perfil.value = await api.obtenerPerfil()
  }

  return {
    token,
    expiraEn,
    perfil,
    motivoDeSalida,
    autenticada,
    debeCambiarContrasena,
    permisos,
    tiene,
    tieneAlguno,
    restaurar,
    iniciar,
    cerrar,
    expirar,
    refrescarPerfil,
  }
})
