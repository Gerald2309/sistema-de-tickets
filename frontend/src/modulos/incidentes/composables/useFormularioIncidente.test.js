import { describe, expect, it } from 'vitest'
import { MAXIMO_TITULO, validarIncidente } from './useFormularioIncidente.js'

const validos = () => ({
  titulo: 'Sin internet en el piso 3',
  descripcion: 'No hay conexión desde las 8.',
  prioridad: 'media',
  categoria: 'red',
  asignadoId: null,
  vencimiento: new Date(Date.now() + 3_600_000),
})

describe('validación del formulario de incidente', () => {
  it('un formulario completo no tiene errores', () => {
    expect(validarIncidente(validos(), true)).toEqual({})
  })

  it('exige título, descripción y vencimiento, sin aceptar solo espacios', () => {
    const errores = validarIncidente({ ...validos(), titulo: '   ', descripcion: '', vencimiento: null }, true)

    expect(Object.keys(errores).sort()).toEqual(['descripcion', 'titulo', 'vencimiento'])
  })

  it('respeta los límites de la API', () => {
    expect(validarIncidente({ ...validos(), titulo: 'a'.repeat(MAXIMO_TITULO + 1) }, true).titulo).toMatch(/máximo/)
  })

  it('al crear, el vencimiento debe ser futuro; al editar, se conserva uno ya pasado', () => {
    const pasado = { ...validos(), vencimiento: new Date(Date.now() - 60_000) }

    expect(validarIncidente(pasado, true).vencimiento).toMatch(/futura/)
    expect(validarIncidente(pasado, false)).toEqual({})
  })
})
