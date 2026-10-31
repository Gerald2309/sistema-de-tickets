import { describe, expect, it } from 'vitest'
import { aConsultaDeRuta, conCambios, enteroPositivo, sinIndefinidos, textoDeConsulta } from './consulta.js'

describe('filtros ↔ consulta de la dirección', () => {
  it('de la dirección solo se acepta un entero positivo', () => {
    expect(enteroPositivo('3')).toBe(3)
    expect(enteroPositivo(['4', '9'])).toBe(4)
    for (const invalido of ['0', '-2', '1.5', 'abc', '', undefined, null]) {
      expect(enteroPositivo(invalido)).toBeUndefined()
    }
  })

  it('un texto vacío o que no es texto se descarta, y uno largo se recorta', () => {
    expect(textoDeConsulta('vpn')).toBe('vpn')
    expect(textoDeConsulta(['red', 'otro'])).toBe('red')
    expect(textoDeConsulta('')).toBeUndefined()
    expect(textoDeConsulta(null)).toBeUndefined()
    expect(textoDeConsulta('x'.repeat(300))).toHaveLength(100)
    expect(textoDeConsulta('abcdef', 3)).toBe('abc')
  })

  it('quita las claves indefinidas o vacías', () => {
    expect(sinIndefinidos({ q: 'vpn', rol: undefined, estado: '', pagina: 2 })).toEqual({ q: 'vpn', pagina: 2 })
  })

  it('la consulta lleva todo como texto y omite la página 1', () => {
    expect(aConsultaDeRuta({ q: 'vpn', rol: 3, pagina: 1, cliente: undefined })).toEqual({ q: 'vpn', rol: '3' })
    expect(aConsultaDeRuta({ pagina: 2 })).toEqual({ pagina: '2' })
  })

  it('cambiar un filtro vuelve a la primera página; cambiar de página no', () => {
    expect(conCambios({ q: 'vpn', pagina: 3 }, { rol: 2 })).toEqual({ q: 'vpn', rol: 2 })
    expect(conCambios({ q: 'vpn', pagina: 3 }, { pagina: 4 })).toEqual({ q: 'vpn', pagina: 4 })
  })
})
