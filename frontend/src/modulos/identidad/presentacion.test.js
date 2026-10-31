import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { GRUPOS_DE_PERMISOS, nombreDePermiso } from './presentacion.js'

describe('presentación de los permisos', () => {
  it('cada permiso del backend tiene nombre y descripción, sin repetidos ni sobrantes', () => {
    // Contrato con el enum ClavePermiso del backend, que está en el mismo repositorio.
    const ruta = resolve(process.cwd(), '../backend/app/Identidad/Dominio/ClavePermiso.php')
    const claves = [...readFileSync(ruta, 'utf8').matchAll(/case \w+ = '([^']+)';/g)].map(([, clave]) => clave)
    const presentadas = GRUPOS_DE_PERMISOS.flatMap((grupo) => grupo.permisos.map((permiso) => permiso.clave))

    expect(claves.length).toBeGreaterThan(0)
    expect(new Set(presentadas).size).toBe(presentadas.length)
    expect([...presentadas].sort()).toEqual([...claves].sort())
  })

  it('un permiso desconocido se muestra con su clave', () => {
    expect(nombreDePermiso('ver_todos')).toBe('Ver todos')
    expect(nombreDePermiso('nuevo_permiso')).toBe('nuevo_permiso')
  })
})
