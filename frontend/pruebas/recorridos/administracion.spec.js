import { randomBytes } from 'node:crypto'
import { api, EJECUCION, expect, iniciarSesionPorApi, test } from './entorno.js'

/**
 * Administrar un usuario desde la interfaz y comprobar, con esa misma persona, que un solicitante no ve ni
 * alcanza lo que no le corresponde (RNF-F31).
 */

const token = () => /** @type {string} */ (process.env.TOKEN_ADMINISTRADOR)
const persona = {
  nombre: `Solicitante ${EJECUCION}`,
  correo: `${EJECUCION}@empresa-ejemplo.com`,
  // Al azar en cada ejecución, con lo que exige la regla de contraseñas.
  clave: `Aa1-${randomBytes(18).toString('base64url')}`,
  // La que elige al entrar: la inicial la puso el administrador.
  propia: `Bb2-${randomBytes(18).toString('base64url')}`,
}
/** @type {number | undefined} */
let idPersona
/** @type {string | undefined} */
let tokenPersona

test.describe.configure({ mode: 'serial' })

test.afterAll(async () => {
  // Las personas no se borran: la de la prueba queda desactivada.
  if (tokenPersona) {
    await api('auth/logout', { metodo: 'POST', token: tokenPersona })
  }
  if (idPersona) {
    await api(`usuarios/${idPersona}`, { metodo: 'PATCH', token: token(), cuerpo: { activo: false } })
  }
})

test('crear un usuario desde el panel lateral', async ({ abrirComo }) => {
  const page = await abrirComo(token(), '/administracion/usuarios')

  await page.getByRole('button', { name: 'Nuevo usuario' }).click()
  const panel = page.getByRole('navigation', { name: 'Nuevo usuario' }).or(page.locator('.panel-lateral'))
  await panel.getByLabel('Nombre *').fill(persona.nombre)
  await panel.getByLabel('Correo *').fill(persona.correo.toUpperCase())
  await panel.getByLabel('Contraseña inicial *').fill(persona.clave)
  await page.getByRole('button', { name: 'Crear usuario' }).click()

  await expect(page.getByText('Usuario creado')).toBeVisible()
  await page.getByPlaceholder('Buscar por nombre o correo').fill(EJECUCION)
  const fila = page.locator('tbody tr').filter({ hasText: persona.nombre })
  await expect(fila).toBeVisible()
  // El correo se guarda en minúsculas.
  await expect(fila).toContainText(persona.correo)
  await expect(fila).toContainText('Solicitante')

  const { datos } = await api(`usuarios?q=${EJECUCION}`, { token: token() })
  idPersona = datos.data[0].id
})

test('con la contraseña inicial, la persona primero elige la suya', async ({ abrirComo }) => {
  tokenPersona = await iniciarSesionPorApi({ correo: persona.correo, clave: persona.clave })
  const page = await abrirComo(tokenPersona, '/incidentes')

  await expect(page).toHaveURL(/\/cambiar-contrasena/)
  // Mientras no la cambie, la API tampoco atiende lo demás.
  expect((await api('incidentes', { token: tokenPersona })).estado).toBe(403)

  await page.getByLabel('Contraseña actual *', { exact: true }).fill(persona.clave)
  await page.getByLabel('Nueva contraseña *', { exact: true }).fill(persona.propia)
  await page.getByLabel('Repita la nueva contraseña *').fill(persona.propia)
  await page.getByRole('button', { name: 'Cambiar contraseña' }).click()

  await expect(page).toHaveURL(/\/incidentes$/)
})

test('un solicitante no ve la administración ni la alcanza por URL', async ({ abrirComo }) => {
  const page = await abrirComo(/** @type {string} */ (tokenPersona), '/incidentes')

  await expect(page.getByRole('heading', { name: 'Incidentes', level: 1 })).toBeVisible()
  const menu = page.getByRole('navigation', { name: 'Secciones' })
  await expect(menu).not.toContainText('Usuarios')
  await expect(menu).not.toContainText('Auditoría')
  // Una persona nueva no tiene incidentes: no ve los de otros.
  await expect(page.getByText('Todavía no hay incidentes')).toBeVisible()

  await page.goto('/administracion/usuarios')
  await expect(page).toHaveURL(/\/sin-permiso$/)

  // La API también lo impide, aunque se salte la interfaz.
  const { estado } = await api('usuarios', { token: tokenPersona })
  expect(estado).toBe(403)
})
