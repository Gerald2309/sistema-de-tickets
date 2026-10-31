import { ADMINISTRADOR, EJECUCION, expect, test } from './entorno.js'

test.describe('sesión', () => {
  test('credenciales inválidas muestran un mensaje que no revela qué falló', async ({ page }) => {
    await page.goto('/ingresar')
    // Un correo que no existe: no consume los intentos de la cuenta real.
    await page.getByLabel('Correo electrónico').fill(`${EJECUCION}@empresa-ejemplo.com`)
    await page.getByLabel('Contraseña', { exact: true }).fill('una contraseña equivocada')
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page.getByText('El correo o la contraseña no son correctos.')).toBeVisible()
    await expect(page.getByLabel('Contraseña', { exact: true })).toHaveValue('')
  })

  test('inicia sesión, vuelve a la ruta pedida y cierra sesión', async ({ page }) => {
    await page.goto('/incidentes')
    await expect(page).toHaveURL(/\/ingresar\?destino=/)

    await page.getByLabel('Correo electrónico').fill(ADMINISTRADOR.correo)
    await page.getByLabel('Contraseña', { exact: true }).fill(ADMINISTRADOR.clave)
    await page.getByRole('button', { name: 'Ingresar' }).click()

    await expect(page).toHaveURL(/\/incidentes$/, { timeout: 15_000 })
    await expect(page.getByRole('heading', { name: 'Incidentes', level: 1 })).toBeVisible()

    await page.getByRole('button', { name: 'Menú de su cuenta' }).click()
    await page.getByText('Cerrar sesión').click()
    await expect(page).toHaveURL(/\/ingresar/, { timeout: 15_000 })
    // La sesión se borró del navegador: volver atrás no la recupera.
    await page.goto('/incidentes')
    await expect(page).toHaveURL(/\/ingresar/)
  })
})
