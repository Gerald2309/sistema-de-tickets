import { api, EJECUCION, expect, test } from './entorno.js'

/**
 * Armar una automatización con bloques desde el editor y comprobar, con la aplicación entera (cola incluida),
 * que actúa sobre un incidente nuevo. La condición pide el código de esta ejecución en el texto: no toca los
 * incidentes de otros recorridos.
 */

const token = () => /** @type {string} */ (process.env.TOKEN_ADMINISTRADOR)
const nombre = `Recorrido ${EJECUCION}`
/** @type {number[]} */
const automatizaciones = []
/** @type {number | undefined} */
let idIncidente

test.describe.configure({ mode: 'serial' })

test.afterAll(async () => {
  for (const id of automatizaciones) {
    await api(`automatizaciones/${id}`, { metodo: 'DELETE', token: token() })
  }
  if (idIncidente) {
    await api(`incidentes/${idIncidente}`, { metodo: 'DELETE', token: token() })
  }
})

test('se arma con bloques en el editor y aparece en la lista', async ({ abrirComo }) => {
  const page = await abrirComo(token(), '/administracion/automatizaciones/nueva')

  await page.getByLabel('Nombre de la automatización').fill(nombre)
  await page.getByRole('button', { name: 'Añadir un paso aquí' }).first().click()
  await page.getByRole('button', { name: /Nota interna/ }).click()
  await page.getByLabel('Texto de la nota').fill('Revisar primero la VPN')
  await page.getByRole('button', { name: 'Guardar cambios' }).click()

  await expect(page.getByText('Automatización guardada')).toBeVisible()
  await expect(page).toHaveURL(/\/administracion\/automatizaciones\/\d+$/)
  automatizaciones.push(Number(new URL(page.url()).pathname.split('/').pop()))

  await page.getByRole('link', { name: 'Administración / Automatizaciones' }).click()
  await expect(page.locator('tbody tr').filter({ hasText: nombre })).toContainText('Todavía no se ha aplicado')
})

test('al crearse un incidente que cumple la condición, deja su nota a nombre de quien la guardó', async () => {
  const { estado, datos } = await api('automatizaciones', {
    metodo: 'POST',
    token: token(),
    cuerpo: {
      nombre: `${nombre} activa`,
      activa: true,
      disparador: 'creacion',
      pasos: [
        {
          tipo: 'condicion',
          campo: 'palabras',
          valores: [EJECUCION],
          si: [{ tipo: 'accion', accion: 'nota_interna', texto: 'Caso {número} revisado por la automatización' }],
        },
      ],
    },
  })
  expect(estado).toBe(201)
  automatizaciones.push(datos.data.id)

  const creado = await api('incidentes', {
    metodo: 'POST',
    token: token(),
    cuerpo: { titulo: `Sin internet ${EJECUCION}`, descripcion: 'No carga ninguna página.' },
  })
  idIncidente = creado.datos.data.id

  // La cola la aplica en segundo plano: se espera a que aparezca la nota.
  await expect
    .poll(async () => (await api(`incidentes/${idIncidente}/mensajes`, { token: token() })).datos.data, {
      timeout: 15_000,
    })
    .toEqual([
      expect.objectContaining({
        cuerpo: expect.stringMatching(/^Caso INC-\d+ revisado por la automatización$/),
        interno: true,
        origen: `${nombre} activa`,
      }),
    ])
})
