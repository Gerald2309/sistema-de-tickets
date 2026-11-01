import { describe, expect, it } from 'vitest'
import { NADA_EDITABLE, partesEditables, puedeDejarSinAsignar, puedeTomar } from './permisos.js'

/** @param {string[]} permisos */
const sesion = (permisos, id = 7) => ({ tiene: (/** @type {string} */ p) => permisos.includes(p), perfil: { id } })
const yo = { id: 7, nombre: 'Camila Ríos' }
const otraPersona = { id: 8, nombre: 'Luis Pardo' }
const soporte = ['editar_todos', 'cambiar_estado']

const propioLibre = { creador: yo, asignado: null }
const propioTomado = { creador: yo, asignado: otraPersona }
const ajenoLibre = { creador: otraPersona, asignado: null }
const ajenoQueAtiendo = { creador: otraPersona, asignado: yo }
const ajenoQueAtiendeOtro = { creador: otraPersona, asignado: otraPersona }

describe('permisos por incidente (como AlcanceIncidentes del servidor)', () => {
  it('el solicitante clasifica lo suyo solo mientras nadie lo ha tomado', () => {
    expect(partesEditables(sesion(['editar_propios']), propioLibre).clasificacion).toBe(true)
    expect(partesEditables(sesion(['editar_propios']), propioTomado).clasificacion).toBe(false)
    expect(partesEditables(sesion(['editar_propios']), ajenoLibre).clasificacion).toBe(false)
  })

  it('quien reparte el trabajo cambia la prioridad de cualquiera, pero no el resto de la clasificación', () => {
    const partes = partesEditables(sesion(['reasignar', 'editar_todos']), ajenoQueAtiendeOtro)
    expect(partes).toMatchObject({ prioridad: true, critica: true, clasificacion: false, plazos: false })
    expect(partesEditables(sesion(soporte), ajenoQueAtiendeOtro).prioridad).toBe(false)
  })

  it('el solicitante clasifica lo suyo, pero los plazos los mueve soporte', () => {
    expect(partesEditables(sesion(['editar_propios']), propioLibre).plazos).toBe(false)
    expect(partesEditables(sesion(soporte), ajenoQueAtiendo).plazos).toBe(true)
    expect(partesEditables(sesion(soporte), ajenoLibre).plazos).toBe(false)
  })

  it('el título y la descripción los corrige solo quien lo reportó, mientras nadie lo ha tomado', () => {
    expect(partesEditables(sesion(['editar_propios']), propioLibre).relato).toBe(true)
    expect(partesEditables(sesion(['editar_propios']), propioTomado).relato).toBe(false)
    expect(partesEditables(sesion(['editar_propios']), ajenoLibre).relato).toBe(false)
    expect(partesEditables(sesion(soporte), ajenoQueAtiendo).relato).toBe(false)
  })

  it('soporte trabaja solo lo que atiende', () => {
    expect(partesEditables(sesion(soporte), ajenoQueAtiendo)).toEqual({
      relato: false,
      clasificacion: true,
      prioridad: true,
      critica: true,
      plazos: true,
      estado: true,
      asignacion: true,
    })
    expect(partesEditables(sesion(soporte), ajenoLibre)).toEqual({ ...NADA_EDITABLE, critica: true })
    expect(partesEditables(sesion(soporte), ajenoQueAtiendeOtro)).toEqual({ ...NADA_EDITABLE, critica: true })
  })

  it('se toma un incidente libre y no cerrado, con el permiso de atenderlo', () => {
    expect(puedeTomar(sesion(soporte), { ...ajenoLibre, estado: 'abierto' })).toBe(true)
    expect(puedeTomar(sesion(soporte), { ...ajenoQueAtiendeOtro, estado: 'abierto' })).toBe(false)
    expect(puedeTomar(sesion(soporte), { ...ajenoLibre, estado: 'cerrado' })).toBe(false)
    expect(puedeTomar(sesion(['editar_propios']), { ...propioLibre, estado: 'abierto' })).toBe(false)
  })

  it('reasignar mueve cualquiera y deja sin asignar; quien atiende solo lo pasa', () => {
    expect(partesEditables(sesion(['reasignar']), ajenoQueAtiendeOtro).asignacion).toBe(true)
    expect(puedeDejarSinAsignar(sesion(['reasignar']))).toBe(true)
    expect(puedeDejarSinAsignar(sesion(soporte))).toBe(false)
  })
})
