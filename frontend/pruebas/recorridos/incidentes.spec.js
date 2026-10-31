import { api, EJECUCION, expect, test } from './entorno.js'

/** Recorridos de incidentes con la sesión de administrador (ve y puede todo). */

const token = () => /** @type {string} */ (process.env.TOKEN_ADMINISTRADOR)
/** @type {number[]} */
const creados = []

/**
 * @param {string} titulo
 * @returns {Promise<{ id: number, codigo: string }>}
 */
async function crearPorApi(titulo) {
  // La API pide segundos sin milésimas.
  const manana = new Date(Date.now() + 24 * 3600 * 1000).toISOString().replace(/\.\d{3}Z$/, 'Z')
  const { estado, datos } = await api('incidentes', {
    metodo: 'POST',
    token: token(),
    cuerpo: {
      titulo,
      descripcion: `Creado por los recorridos (${EJECUCION}).`,
      prioridad: 'baja',
      categoria: 'otros',
      fecha_vencimiento: manana,
    },
  })
  expect(estado, JSON.stringify(datos)).toBe(201)
  creados.push(datos.data.id)
  return datos.data
}

test.describe.configure({ mode: 'serial' })

test.afterAll(async () => {
  // Lo creado por los recorridos no queda en el listado.
  if (creados.length) {
    await api('incidentes/lote', { metodo: 'DELETE', token: token(), cuerpo: { ids: creados } })
  }
})

test('crear un incidente lleva a su detalle, en su pestaña', async ({ abrirComo }) => {
  const page = await abrirComo(token(), '/incidentes/nuevo')
  const titulo = `Impresora sin tóner ${EJECUCION}`

  // Enviar vacío marca los obligatorios y no envía nada.
  await page.getByRole('button', { name: 'Crear incidente' }).click()
  await expect(page.getByText('Escriba un título.')).toBeVisible()

  await page.getByLabel('Título *').fill(titulo)
  await page.getByLabel('Descripción *').fill('La impresora del piso 2 no imprime desde la mañana.')
  await page.getByLabel('Fecha de vencimiento *').click()
  await page.getByRole('button', { name: 'Mañana 8:00' }).click()
  await page.getByRole('button', { name: 'Crear incidente' }).click()

  await expect(page).toHaveURL(/\/incidentes\/\d+$/)
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(titulo)
  const id = Number(page.url().split('/').pop())
  creados.push(id)
  await expect(page.getByRole('tablist', { name: 'Incidentes abiertos' })).toContainText(
    `INC-${String(id).padStart(6, '0')}`,
  )
})

test('buscar y filtrar dejan los filtros en la dirección', async ({ abrirComo }) => {
  const { codigo } = await crearPorApi(`Monitor parpadea ${EJECUCION}`)
  const page = await abrirComo(token(), '/incidentes')

  await page.getByPlaceholder('Buscar por título o descripción').fill(`Monitor parpadea ${EJECUCION}`)
  await expect(page).toHaveURL(/q=Monitor/)
  await expect(page.locator('tbody tr')).toHaveCount(1)
  await expect(page.locator('tbody tr').first()).toContainText(codigo)

  await page.getByRole('button', { name: 'Filtrar por estado' }).click()
  await page
    .getByRole('option', { name: 'Resuelto' })
    .or(page.getByText('Resuelto', { exact: true }))
    .first()
    .click()
  await expect(page).toHaveURL(/estado=resuelto/)
  await expect(page.getByText('No hay incidentes con estos filtros')).toBeVisible()

  // Recargar reproduce la misma vista.
  await page.reload()
  await expect(page.getByText('Estado: Resuelto')).toBeVisible()
})

test('llevar un incidente a resuelto pide la solución y la muestra', async ({ abrirComo }) => {
  const { id } = await crearPorApi(`Sin acceso a la VPN ${EJECUCION}`)
  const page = await abrirComo(token(), `/incidentes/${id}`)

  await page.getByRole('button', { name: 'Cambiar estado' }).click()
  await page
    .getByRole('menuitem', { name: 'En progreso' })
    .or(page.getByText('En progreso', { exact: true }).last())
    .click()
  await expect(page.getByText(/en progreso/i).first()).toBeVisible()

  await page.getByRole('button', { name: 'Cambiar estado' }).click()
  await page.getByText('Resuelto', { exact: true }).last().click()
  const dialogo = page.getByRole('dialog')
  await expect(dialogo.getByRole('button', { name: 'Resolver' })).toBeDisabled()
  await dialogo.getByLabel('Solución *').fill('Se renovó el certificado del cliente VPN.')
  await dialogo.getByRole('button', { name: 'Resolver' }).click()

  await expect(page.getByRole('heading', { name: 'Solución' })).toBeVisible()
  await expect(page.getByText('Se renovó el certificado del cliente VPN.')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Historial' })).toBeVisible()
  await expect(page.getByText('Abierto → En progreso')).toBeVisible()
})

test('acciones en lote: cambiar la prioridad de varios a la vez', async ({ abrirComo }) => {
  const a = await crearPorApi(`Lote A ${EJECUCION}`)
  const b = await crearPorApi(`Lote B ${EJECUCION}`)
  const page = await abrirComo(token(), `/incidentes?q=${encodeURIComponent(`Lote ${EJECUCION}`)}`)

  await page.getByLabel(`Seleccionar ${a.codigo}`).check()
  await page.getByLabel(`Seleccionar ${b.codigo}`).check()
  const lote = page.getByRole('region', { name: 'Acciones en lote' })
  await expect(lote).toContainText('2 seleccionados')
  await lote.getByRole('button', { name: 'Prioridad' }).click()
  await page.getByText('Crítica', { exact: true }).last().click()

  await expect(page.getByText('2 incidentes actualizados.')).toBeVisible()
  const { datos } = await api(`incidentes/${a.id}`, { token: token() })
  expect(datos.data.prioridad).toBe('critica')
})

test('eliminar en lote pide confirmación y borra solo los elegidos', async ({ abrirComo }) => {
  const a = await crearPorApi(`Borrar A ${EJECUCION}`)
  const b = await crearPorApi(`Borrar B ${EJECUCION}`)
  const queda = await crearPorApi(`Borrar C ${EJECUCION}`)
  const page = await abrirComo(token(), `/incidentes?q=${encodeURIComponent(`Borrar ${EJECUCION}`)}`)
  await expect(page.locator('tbody tr')).toHaveCount(3)

  await page.getByLabel(`Seleccionar ${a.codigo}`).check()
  await page.getByLabel(`Seleccionar ${b.codigo}`).check()
  await page.getByRole('region', { name: 'Acciones en lote' }).getByRole('button', { name: 'Eliminar' }).click()
  const dialogo = page.getByRole('alertdialog')
  await expect(dialogo).toContainText('¿Eliminar 2 incidentes?')
  await dialogo.getByRole('button', { name: 'Eliminar' }).click()

  await expect(page.getByText('2 incidentes eliminados.')).toBeVisible()
  await expect(page.locator('tbody tr')).toHaveCount(1)
  expect((await api(`incidentes/${a.id}`, { token: token() })).estado).toBe(404)
  expect((await api(`incidentes/${b.id}`, { token: token() })).estado).toBe(404)
  expect((await api(`incidentes/${queda.id}`, { token: token() })).estado).toBe(200)
  creados.splice(creados.indexOf(a.id), 1)
  creados.splice(creados.indexOf(b.id), 1)
})

test('descargar el listado en CSV con los filtros activos', async ({ abrirComo }) => {
  await crearPorApi(`Descarga ${EJECUCION}`)
  const page = await abrirComo(token(), `/incidentes?q=${encodeURIComponent(`Descarga ${EJECUCION}`)}`)
  await expect(page.locator('tbody tr')).toHaveCount(1)

  await page.getByRole('button', { name: /Descargar/ }).click()
  const [descarga] = await Promise.all([page.waitForEvent('download'), page.getByText('CSV', { exact: true }).click()])

  expect(descarga.suggestedFilename()).toMatch(/^incidentes-\d{4}-\d{2}-\d{2}\.csv$/)
  const ruta = await descarga.path()
  const { readFile } = await import('node:fs/promises')
  const contenido = await readFile(ruta, 'utf8')
  expect(contenido).toContain(`Descarga ${EJECUCION}`)
  // Solo lo filtrado: la cabecera y una fila.
  expect(contenido.trim().split(/\r?\n/)).toHaveLength(2)
})
