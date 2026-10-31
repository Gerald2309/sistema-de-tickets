import { describe, expect, it } from 'vitest'
import { decidirAcceso } from './acceso.js'
import { rutas } from './index.js'

const sinSesion = { autenticada: false, tieneAlguno: () => false }
const conPermisos = (...permisos) => ({
  autenticada: true,
  tieneAlguno: (...pedidos) => pedidos.some((pedido) => permisos.includes(pedido)),
})

describe('decidirAcceso', () => {
  it('sin sesión, toda ruta no pública lleva al inicio de sesión', () => {
    expect(decidirAcceso('autenticado', sinSesion)).toEqual({ tipo: 'ingresar' })
    expect(decidirAcceso('ver_todos', sinSesion)).toEqual({ tipo: 'ingresar' })
  })

  it('la ruta pública solo se abre sin sesión', () => {
    expect(decidirAcceso('publica', sinSesion)).toEqual({ tipo: 'permitir' })
    expect(decidirAcceso('publica', conPermisos())).toEqual({ tipo: 'inicio' })
  })

  it('basta con tener uno de los permisos declarados', () => {
    expect(decidirAcceso(['ver_propios', 'ver_todos'], conPermisos('ver_todos'))).toEqual({ tipo: 'permitir' })
    expect(decidirAcceso('ver_auditoria', conPermisos('ver_todos'))).toEqual({ tipo: 'sin-permiso' })
  })

  it('una ruta sin permiso declarado se rechaza aunque haya sesión (denegar por defecto)', () => {
    expect(decidirAcceso(undefined, conPermisos('ver_todos'))).toEqual({ tipo: 'sin-permiso' })
    expect(decidirAcceso([], conPermisos('ver_todos'))).toEqual({ tipo: 'sin-permiso' })
  })
})

describe('rutas de la aplicación', () => {
  /** @param {import('vue-router').RouteRecordRaw[]} lista */
  const todas = (lista) => lista.flatMap((ruta) => [ruta, ...todas(ruta.children ?? [])])

  it('toda ruta declara el permiso que exige', () => {
    const sinPermiso = todas(rutas).filter((ruta) => !ruta.redirect && ruta.meta?.permiso === undefined)

    expect(sinPermiso.map((ruta) => ruta.path)).toEqual([])
  })
})
