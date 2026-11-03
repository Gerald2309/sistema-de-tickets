import { describe, expect, it } from 'vitest'
import { erroresDelPaso, insertar, personasNombradas, quitar, rutaDe, tieneAcciones } from './flujo.js'

const asignar = (/** @type {number} */ id) => ({
  tipo: /** @type {const} */ ('accion'),
  accion: 'asignar',
  usuario_id: id,
})
const condicion = (/** @type {any[]} */ si = [], /** @type {any[]} */ no = []) => ({
  tipo: /** @type {const} */ ('condicion'),
  campo: /** @type {const} */ ('prioridad'),
  valores: ['alta'],
  niega: false,
  si,
  no,
})

describe('flujo de una automatización', () => {
  it('una acción se inserta en su lugar', () => {
    const pasos = [asignar(1), asignar(3)]
    insertar(pasos, 1, asignar(2))
    expect(pasos.map((paso) => paso.usuario_id)).toEqual([1, 2, 3])
  })

  it('una condición se lleva los pasos que venían después a su camino Sí', () => {
    const pasos = /** @type {any[]} */ ([asignar(1), asignar(2), asignar(3)])
    const nueva = condicion()
    insertar(pasos, 1, nueva)
    expect(pasos).toEqual([asignar(1), { ...condicion(), si: [asignar(2), asignar(3)] }])
  })

  it('al quitar una condición su camino Sí ocupa su lugar y el No se descarta', () => {
    const pasos = /** @type {any[]} */ ([asignar(1), condicion([asignar(2)], [asignar(9)])])
    quitar(pasos, 1)
    expect(pasos).toEqual([asignar(1), asignar(2)])
  })

  it('sabe si hace algo en alguno de sus caminos', () => {
    expect(tieneAcciones([condicion([], [asignar(1)])])).toBe(true)
    expect(tieneAcciones([condicion()])).toBe(false)
  })

  it('encuentra la ruta de un paso y las personas que nombra', () => {
    const buscado = asignar(7)
    const pasos = /** @type {any[]} */ ([
      condicion([], [buscado, { tipo: 'accion', accion: 'avisar', destinatario: 'usuario', id: 8 }]),
    ])
    expect(rutaDe(pasos, buscado)).toBe('pasos.0.no.0')
    expect(personasNombradas(pasos)).toEqual([7, 8])
  })

  it('los errores de un paso son los suyos, no los de los pasos de sus caminos', () => {
    const errores = {
      'pasos.0.valores': ['Elija una opción.'],
      'pasos.0.si.0.usuario_id': ['Esa persona no atiende.'],
      'pasos.1': ['Después de una condición…'],
    }
    expect(erroresDelPaso(errores, 'pasos.0')).toEqual(['Elija una opción.'])
    expect(erroresDelPaso(errores, 'pasos.0.si.0')).toEqual(['Esa persona no atiende.'])
  })
})
