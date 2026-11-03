import { describe, expect, it } from 'vitest'
import { evaluar } from './permisos.js'
import { enlaceAlCodigo } from './repositorio.js'

/** @param {Partial<import('./permisos.js').Situacion>} cambios */
const caso = (cambios) => evaluar({ rol: 'soporte', accion: 'ver', creador: 'otra', asignado: 'nadie', ...cambios })

describe('simulador de permisos', () => {
  it('sin sesión responde 401 antes de mirar el incidente', () => {
    const { estado, capas } = caso({ rol: 'sinSesion' })
    expect(estado).toBe(401)
    expect(capas.filter((c) => c.resultado === 'no-llega')).toHaveLength(3)
  })

  it('el solicitante no ve lo ajeno: 404, igual que si no existiera', () => {
    expect(caso({ rol: 'solicitante', creador: 'otra' }).estado).toBe(404)
    expect(caso({ rol: 'solicitante', creador: 'yo' }).estado).toBe(200)
  })

  it('tomar exige cambiar_estado y que siga libre', () => {
    expect(caso({ accion: 'tomar' }).estado).toBe(200)
    expect(caso({ accion: 'tomar', asignado: 'otra' }).estado).toBe(409)
    expect(caso({ rol: 'solicitante', creador: 'yo', accion: 'tomar' }).estado).toBe(403)
  })

  it('el estado lo cambia solo quien lo atiende', () => {
    expect(caso({ accion: 'estado', asignado: 'yo' }).estado).toBe(200)
    expect(caso({ accion: 'estado', asignado: 'otra' }).estado).toBe(403)
    expect(caso({ rol: 'administrador', accion: 'estado', asignado: 'otra' }).estado).toBe(403)
  })

  it('marcarlo como crítico mueve los plazos: el solicitante no puede ni en el suyo libre', () => {
    const { estado, capas } = caso({ rol: 'solicitante', creador: 'yo', accion: 'critico' })
    expect(estado).toBe(403)
    expect(capas.find((c) => c.nombre === 'Permiso por campo')?.resultado).toBe('falla')
    expect(caso({ rol: 'administrador', accion: 'critico', asignado: 'otra' }).estado).toBe(200)
  })

  it('el título lo corrige quien lo reportó mientras nadie lo ha tomado', () => {
    expect(caso({ rol: 'solicitante', creador: 'yo', accion: 'titulo' }).estado).toBe(200)
    expect(caso({ rol: 'solicitante', creador: 'yo', asignado: 'otra', accion: 'titulo' }).estado).toBe(403)
  })

  it('la auditoría es solo del administrador', () => {
    expect(caso({ accion: 'auditoria' }).estado).toBe(403)
    expect(caso({ rol: 'administrador', accion: 'auditoria' }).estado).toBe(200)
  })
})

describe('enlaces al código', () => {
  it('arma la dirección en GitHub de una ruta de archivo, sin el comentario', () => {
    expect(enlaceAlCodigo('backend/routes/api.php')).toBe(
      'https://github.com/Gerald2309/sistema-de-tickets/blob/main/backend/routes/api.php',
    )
    expect(enlaceAlCodigo('docker-compose.yml · cola')).toMatch(/\/docker-compose\.yml$/)
  })

  it('no inventa enlaces para lo que no es un archivo', () => {
    expect(enlaceAlCodigo('')).toBeNull()
    expect(enlaceAlCodigo('el editor')).toBeNull()
  })
})
