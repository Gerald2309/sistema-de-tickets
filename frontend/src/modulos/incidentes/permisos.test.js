import { describe, expect, it } from 'vitest'
import { partesEditables, puedeModificar } from './permisos.js'

/** @param {string[]} permisos */
const sesion = (permisos, id = 7) => ({ tiene: (/** @type {string} */ p) => permisos.includes(p), perfil: { id } })
const propio = { creador: { id: 7, nombre: 'Camila Ríos' } }
const ajeno = { creador: { id: 8, nombre: 'Luis Pardo' } }

describe('permisos por incidente (como AlcanceIncidentes del servidor)', () => {
  it('editar_propios solo alcanza el contenido de lo que la persona creó', () => {
    expect(partesEditables(sesion(['editar_propios']), propio).contenido).toBe(true)
    expect(partesEditables(sesion(['editar_propios']), ajeno).contenido).toBe(false)
  })

  it('editar_todos alcanza cualquier incidente', () => {
    expect(partesEditables(sesion(['editar_todos']), ajeno).contenido).toBe(true)
  })

  it('el estado y la asignación dependen de su propio permiso', () => {
    expect(partesEditables(sesion(['cambiar_estado']), ajeno)).toEqual({
      contenido: false,
      estado: true,
      asignacion: false,
    })
    expect(partesEditables(sesion(['reasignar']), ajeno)).toEqual({ contenido: false, estado: false, asignacion: true })
  })

  it('«Editar» se ofrece si se puede cambiar al menos una parte', () => {
    expect(puedeModificar(sesion(['ver_propios']), propio)).toBe(false)
    expect(puedeModificar(sesion(['reasignar']), ajeno)).toBe(true)
  })
})
