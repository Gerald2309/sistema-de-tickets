import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { test as base, expect } from '@playwright/test'

/**
 * Entorno de los recorridos: credenciales, llamadas directas a la API para preparar y limpiar datos, y páginas
 * con la sesión ya abierta (así solo la prueba de inicio de sesión pasa por el formulario y no se agota el
 * límite de intentos por correo).
 */

export const URL_APLICACION = process.env.URL_APLICACION ?? 'http://localhost:8000'
const CLAVE_DE_SESION = 'mesa-de-ayuda.sesion'

/**
 * Las variables que usa Docker Compose: `.env.example` y encima `.env` si existe (docker-compose.yml).
 * @returns {Record<string, string>}
 */
function variablesDeCompose() {
  const raiz = resolve(import.meta.dirname, '..', '..', '..')
  /** @type {Record<string, string>} */
  const variables = {}
  for (const archivo of ['.env.example', '.env']) {
    let contenido
    try {
      contenido = readFileSync(resolve(raiz, archivo), 'utf8')
    } catch {
      continue
    }
    for (const linea of contenido.split(/\r?\n/)) {
      const coincidencia = /^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/.exec(linea)
      if (coincidencia) {
        variables[coincidencia[1]] = coincidencia[2].replace(/^"(.*)"$/, '$1')
      }
    }
  }
  return variables
}

const compose = variablesDeCompose()
export const ADMINISTRADOR = {
  correo: process.env.ADMIN_EMAIL ?? compose.ADMIN_EMAIL,
  clave: process.env.ADMIN_PASSWORD ?? compose.ADMIN_PASSWORD,
}

/** Sufijo único por ejecución, para reconocer y limpiar lo que crean las pruebas. */
export const EJECUCION = `e2e-${Date.now().toString(36)}`

/**
 * Llamada a la API como lo haría la aplicación.
 * @param {string} ruta
 * @param {{ metodo?: string, token?: string, cuerpo?: unknown }} [opciones]
 * @returns {Promise<{ estado: number, datos: any }>}
 */
export async function api(ruta, { metodo = 'GET', token, cuerpo } = {}) {
  const respuesta = await fetch(`${URL_APLICACION}/api/v1/${ruta}`, {
    method: metodo,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: cuerpo === undefined ? undefined : JSON.stringify(cuerpo),
  })
  const texto = await respuesta.text()
  return { estado: respuesta.status, datos: texto ? JSON.parse(texto) : null }
}

/**
 * @param {{ correo: string, clave: string }} credenciales
 * @returns {Promise<string>} token
 */
export async function iniciarSesionPorApi({ correo, clave }) {
  const { estado, datos } = await api('auth/login', { metodo: 'POST', cuerpo: { email: correo, password: clave } })
  if (estado !== 200) {
    throw new Error(`No se pudo iniciar sesión como ${correo} (${estado}). ¿Está la aplicación en Docker?`)
  }
  return datos.data.token
}

/**
 * Pruebas con una página que ya tiene la sesión del token indicado.
 * @type {import('@playwright/test').TestType<{ abrirComo: (token: string, ruta: string) => Promise<import('@playwright/test').Page> }, {}>}
 */
export const test = base.extend({
  abrirComo: async ({ page }, usar) => {
    await usar(async (token, ruta) => {
      await page.addInitScript(
        ([clave, valor]) => {
          if (!sessionStorage.getItem(clave)) {
            sessionStorage.setItem(clave, valor)
          }
        },
        [CLAVE_DE_SESION, JSON.stringify({ token, expiraEn: null })],
      )
      await page.goto(ruta)
      return page
    })
  },
})

export { expect }
