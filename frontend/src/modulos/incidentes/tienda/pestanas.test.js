import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import { MAXIMO_DE_PESTANAS, usePestanas } from './pestanas.js'

const CLAVE = 'mesa-de-ayuda.pestanas'

describe('pestañas de tickets', () => {
  beforeEach(() => {
    sessionStorage.clear()
    setActivePinia(createPinia())
  })

  it('abrir un incidente ya abierto lo activa sin duplicarlo', () => {
    const pestanas = usePestanas()
    pestanas.abrir(1)
    pestanas.abrir(2)
    pestanas.abrir(1)

    expect(pestanas.lista.map((p) => p.id)).toEqual([1, 2])
    expect(pestanas.activa).toBe(1)
  })

  it(`con ${MAXIMO_DE_PESTANAS} abiertas, la novena cierra la más antigua sin cambios sin guardar`, () => {
    const pestanas = usePestanas()
    for (let id = 1; id <= MAXIMO_DE_PESTANAS; id++) pestanas.abrir(id)
    pestanas.marcarSinGuardar(1, true)

    expect(pestanas.abrir(9)).toBe(true)
    expect(pestanas.lista.map((p) => p.id)).toEqual([1, 3, 4, 5, 6, 7, 8, 9])
  })

  it('si todas tienen cambios sin guardar, no abre otra', () => {
    const pestanas = usePestanas()
    for (let id = 1; id <= MAXIMO_DE_PESTANAS; id++) {
      pestanas.abrir(id)
      pestanas.marcarSinGuardar(id, true)
    }

    expect(pestanas.abrir(9)).toBe(false)
    expect(pestanas.lista).toHaveLength(MAXIMO_DE_PESTANAS)
  })

  it('al cerrar la activa indica la de la derecha; si no hay, la de la izquierda; si no, el listado', () => {
    const pestanas = usePestanas()
    ;[1, 2, 3].forEach((id) => pestanas.abrir(id))
    pestanas.abrir(2)

    expect(pestanas.cerrar(2)).toBe(3)
    pestanas.abrir(3)
    expect(pestanas.cerrar(3)).toBe(1)
    pestanas.abrir(1)
    expect(pestanas.cerrar(1)).toBeNull()
  })

  it('cerrar una inactiva no cambia la activa', () => {
    const pestanas = usePestanas()
    ;[1, 2].forEach((id) => pestanas.abrir(id))

    expect(pestanas.cerrar(1)).toBe(2)
    expect(pestanas.activa).toBe(2)
  })

  it('sobrevive a recargar con solo id, código, título y estado; se olvida al cerrar sesión', async () => {
    const pestanas = usePestanas()
    pestanas.abrir(5)
    pestanas.describir(5, { codigo: 'INC-000005', titulo: 'Impresora', estado: 'abierto' })
    pestanas.marcarSinGuardar(5, true)
    await nextTick()

    expect(JSON.parse(sessionStorage.getItem(CLAVE) ?? '[]')).toEqual([
      { id: 5, codigo: 'INC-000005', titulo: 'Impresora', estado: 'abierto' },
    ])

    setActivePinia(createPinia())
    const recargadas = usePestanas()
    expect(recargadas.lista[0]).toMatchObject({ id: 5, codigo: 'INC-000005', sinGuardar: false })

    recargadas.olvidar()
    await nextTick()
    expect(recargadas.lista).toEqual([])
    expect(JSON.parse(sessionStorage.getItem(CLAVE) ?? '[]')).toEqual([])
  })

  it('ignora lo guardado que no tiene forma de pestaña', () => {
    sessionStorage.setItem(CLAVE, JSON.stringify([{ id: 'x' }, { id: 3, titulo: 4 }, null]))

    expect(usePestanas().lista).toEqual([
      { id: 3, codigo: null, titulo: '', estado: null, pantalla: 'detalle', sinGuardar: false },
    ])
  })
})
