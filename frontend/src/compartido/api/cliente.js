import axios from 'axios'
import { aErrorDeApi } from './errores.js'

/**
 * Cliente HTTP único de la aplicación: lo usan solo las funciones `api/` de cada módulo (arquitectura §6).
 * No conoce la sesión: el núcleo le indica cómo obtener el token y qué hacer cuando la API responde 401.
 */
export const http = axios.create({
  baseURL: '/api/v1',
  timeout: 20_000,
  headers: { Accept: 'application/json' },
})

/** @type {{ obtenerToken: () => string | null, alExpirar: () => void }} */
const sesion = { obtenerToken: () => null, alExpirar: () => {} }

/**
 * Conecta el cliente con la sesión. Lo llama el núcleo una vez al arrancar.
 * @param {{ obtenerToken: () => string | null, alExpirar: () => void }} conexion
 * @returns {void}
 */
export function conectarSesion(conexion) {
  sesion.obtenerToken = conexion.obtenerToken
  sesion.alExpirar = conexion.alExpirar
}

/**
 * @param {import('axios').InternalAxiosRequestConfig} configuracion
 * @returns {boolean} la petición va al mismo origen que la aplicación
 */
function esDelMismoOrigen(configuracion) {
  const destino = new URL(http.getUri(configuracion), window.location.origin)
  return destino.origin === window.location.origin
}

// El token solo viaja al mismo origen (RNF-F14): si alguna vez se llamara a una URL completa de otro
// sitio, iría sin credencial.
http.interceptors.request.use((configuracion) => {
  const token = sesion.obtenerToken()
  if (token && esDelMismoOrigen(configuracion)) {
    configuracion.headers.set('Authorization', `Bearer ${token}`)
  }
  return configuracion
})

// Todo error sale como ErrorDeApi. Un 401 con sesión abierta significa que el token venció o fue
// revocado: la sesión decide (una sola vez aunque fallen varias peticiones a la vez).
http.interceptors.response.use(
  (respuesta) => respuesta,
  (error) => {
    const problema = aErrorDeApi(error)
    if (problema.estado === 401 && sesion.obtenerToken()) {
      sesion.alExpirar()
    }
    return Promise.reject(problema)
  },
)
