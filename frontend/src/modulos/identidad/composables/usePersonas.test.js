import { describe, expect, it } from 'vitest'
import { agruparPorRol } from './usePersonas.js'

const persona = (id, nombre, rol) => ({ id, nombre, activo: true, rol })

describe('personas para asignar agrupadas por rol', () => {
  it('ordena los niveles de soporte y deja al administrador al final, cada grupo con su subtítulo', () => {
    const opciones = agruparPorRol([
      persona(1, 'Ana', 'Administrador'),
      persona(2, 'Diego', 'Soporte N2'),
      persona(3, 'Cristina', 'Soporte N1'),
      persona(4, 'Erik', 'Soporte N1'),
    ])

    expect(opciones.map((opcion) => ('type' in opcion ? `# ${opcion.title}` : opcion.nombre))).toEqual([
      '# Soporte N1',
      'Cristina',
      'Erik',
      '# Soporte N2',
      'Diego',
      '# Administrador',
      'Ana',
    ])
  })

  it('sin rol, las deja sin subtítulos', () => {
    expect(agruparPorRol([persona(1, 'Ana', undefined)])).toEqual([persona(1, 'Ana', undefined)])
  })
})
