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

  it('soporte trabaja solo lo que atiende', () => {
    expect(partesEditables(sesion(soporte), ajenoQueAtiendo)).toEqual({
      clasificacion: true,
      estado: true,
      asignacion: true,
    })
    expect(partesEditables(sesion(soporte), ajenoLibre)).toEqual(NADA_EDITABLE)
    expect(partesEditables(sesion(soporte), ajenoQueAtiendeOtro)).toEqual(NADA_EDITABLE)
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
