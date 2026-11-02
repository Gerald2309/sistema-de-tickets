import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { describirDispositivo, GRUPOS_DE_PERMISOS, nombreDePermiso } from './presentacion.js'

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

describe('describirDispositivo', () => {
  it('reconoce el navegador y el sistema aunque el agente nombre a otros', () => {
    const edge =
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 Edg/130.0'
    const chromeAndroid =
      'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36'
    const safariIphone =
      'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'

    expect(describirDispositivo(edge)).toBe('Edge en Windows')
    expect(describirDispositivo(chromeAndroid)).toBe('Chrome en Android')
    expect(describirDispositivo(safariIphone)).toBe('Safari en iPhone')
  })

  it('sin agente o con uno desconocido no inventa', () => {
    expect(describirDispositivo(null)).toBe('Dispositivo desconocido')
    expect(describirDispositivo('curl/8.5.0')).toBe('Otro dispositivo')
  })
})
