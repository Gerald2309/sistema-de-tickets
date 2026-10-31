import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { ACCIONES, presentarAccion } from './presentacion.js'

describe('presentación de la auditoría', () => {
  it('cada acción del backend tiene nombre e ícono, y no sobra ninguna', () => {
    // Contrato con el enum AccionAuditoria del backend, que está en el mismo repositorio.
    const ruta = resolve(process.cwd(), '../backend/app/Auditoria/Dominio/AccionAuditoria.php')
    const acciones = [...readFileSync(ruta, 'utf8').matchAll(/case \w+ = '([^']+)';/g)].map(([, accion]) => accion)

    expect(acciones.length).toBeGreaterThan(0)
    expect(Object.keys(ACCIONES).sort()).toEqual([...acciones].sort())
    expect(Object.values(ACCIONES).every((accion) => accion.icono && accion.nombre)).toBe(true)
  })

  it('una acción desconocida se muestra con su valor', () => {
    expect(presentarAccion('accion_futura').nombre).toBe('accion_futura')
  })
})
