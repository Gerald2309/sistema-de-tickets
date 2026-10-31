import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { crearAgrupador } from './agrupador.js'

describe('agrupador de avisos', () => {
  beforeEach(() => vi.useFakeTimers())
  afterEach(() => vi.useRealTimers())

  it('entrega una sola vez los ids que llegaron en el intervalo, sin repetidos', () => {
    const alVaciar = vi.fn()
    const agrupador = crearAgrupador(300, alVaciar)

    agrupador.agregar([1, 2])
    vi.advanceTimersByTime(100)
    agrupador.agregar([2, 3])
    expect(alVaciar).not.toHaveBeenCalled()

    vi.advanceTimersByTime(200)
    expect(alVaciar).toHaveBeenCalledOnce()
    expect(alVaciar).toHaveBeenCalledWith([1, 2, 3])
  })

  it('lo que llega después empieza otra tanda', () => {
    const alVaciar = vi.fn()
    const agrupador = crearAgrupador(300, alVaciar)

    agrupador.agregar([1])
    vi.advanceTimersByTime(300)
    agrupador.agregar([4])
    vi.advanceTimersByTime(300)

    expect(alVaciar.mock.calls).toEqual([[[1]], [[4]]])
  })

  it('descarta lo que no es un id y lo cancelado', () => {
    const alVaciar = vi.fn()
    const agrupador = crearAgrupador(300, alVaciar)

    agrupador.agregar(['7', null, 2.5])
    agrupador.cancelar()
    vi.advanceTimersByTime(1000)

    expect(alVaciar).not.toHaveBeenCalled()
  })
})
