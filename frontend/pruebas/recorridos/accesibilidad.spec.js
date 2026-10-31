import AxeBuilder from '@axe-core/playwright'
import { api, expect, test } from './entorno.js'

/**
 * Accesibilidad automática (RNF-F07): axe con las reglas de WCAG 2.2 A y AA en cada pantalla principal. axe
 * encuentra una parte de los problemas; el resto se revisa con teclado y lector de pantalla.
 */

const token = () => /** @type {string} */ (process.env.TOKEN_ADMINISTRADOR)

// Sin animaciones de entrada: axe mide el contraste de lo que ve, y a mitad de un fundido el texto aún es tenue.
test.use({ contextOptions: { reducedMotion: 'reduce' } })

/**
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<void>}
 */
async function sinViolaciones(page) {
  // Se espera a que llegue el contenido de la pantalla.
  await page.waitForLoadState('networkidle')
  const { violations } = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze()
  const resumen = violations.map((v) => `${v.id} (${v.impact}): ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`)
  expect(resumen).toEqual([])
}

test('inicio de sesión', async ({ page }) => {
  await page.goto('/ingresar')
  await sinViolaciones(page)
})

for (const [nombre, ruta] of [
  ['panel', '/panel'],
  ['listado', '/incidentes'],
  ['formulario', '/incidentes/nuevo'],
  ['usuarios', '/administracion/usuarios'],
  ['roles y permisos', '/administracion/roles'],
  ['empresas', '/administracion/empresas'],
  ['auditoría', '/administracion/auditoria'],
  ['mi cuenta', '/mi-cuenta'],
]) {
  test(nombre, async ({ abrirComo }) => {
    const page = await abrirComo(token(), ruta)
    await sinViolaciones(page)
  })
}

test('detalle', async ({ abrirComo }) => {
  const { datos } = await api('incidentes?per_page=1', { token: token() })
  const page = await abrirComo(token(), `/incidentes/${datos.data[0].id}`)
  await sinViolaciones(page)
})
