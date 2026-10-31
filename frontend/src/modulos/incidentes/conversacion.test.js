import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'
import { LIMITES_DE_MENSAJE, revisarImagenes, tamanoLegible } from './conversacion.js'

/**
 * @param {string} nombre
 * @param {string} tipo
 * @param {number} bytes
 */
const archivo = (nombre, tipo, bytes = 10) => new File([new Uint8Array(bytes)], nombre, { type: tipo })

describe('mensajes de la conversación', () => {
  it('acepta JPG, PNG y WebP y rechaza el resto con el motivo', () => {
    const { aceptadas, rechazos } = revisarImagenes(0, [
      archivo('a.png', 'image/png'),
      archivo('b.jpg', 'image/jpeg'),
      archivo('c.webp', 'image/webp'),
      archivo('d.svg', 'image/svg+xml'),
      archivo('e.pdf', 'application/pdf'),
    ])

    expect(aceptadas.map((imagen) => imagen.name)).toEqual(['a.png', 'b.jpg', 'c.webp'])
    expect(rechazos).toEqual([
      'd.svg: solo se admiten imágenes JPG, PNG o WebP.',
      'e.pdf: solo se admiten imágenes JPG, PNG o WebP.',
    ])
  })

  it('rechaza las imágenes de más de 5 MB y las que pasan del máximo por mensaje', () => {
    const grande = archivo('grande.png', 'image/png', 5 * 1024 * 1024 + 1)
    const { aceptadas, rechazos } = revisarImagenes(3, [
      grande,
      archivo('a.png', 'image/png'),
      archivo('b.png', 'image/png'),
    ])

    expect(aceptadas.map((imagen) => imagen.name)).toEqual(['a.png'])
    expect(rechazos).toEqual(['grande.png: supera los 5 MB.', 'b.png: un mensaje lleva como máximo 4 imágenes.'])
  })

  it('muestra los tamaños en KB o MB', () => {
    expect(tamanoLegible(2048)).toBe('2 KB')
    expect(tamanoLegible(1.5 * 1024 * 1024)).toBe('1,5 MB')
  })

  it('usa los mismos límites que valida la API', () => {
    // Vitest corre desde la carpeta del frontend; el backend está al lado, en el mismo repositorio.
    const ruta = resolve(process.cwd(), '../backend/app/Incidentes/Http/Requests/EscribirMensajeRequest.php')
    const peticion = readFileSync(ruta, 'utf8')
    const constante = (/** @type {string} */ nombre) => peticion.match(new RegExp(`const ${nombre} = ([^;]+);`))?.[1]

    expect(constante('MAXIMO_CUERPO')).toBe(String(LIMITES_DE_MENSAJE.caracteres))
    expect(constante('MAXIMO_IMAGENES')).toBe(String(LIMITES_DE_MENSAJE.imagenes))
    expect(constante('MAXIMO_KB_POR_IMAGEN')).toBe(`${LIMITES_DE_MENSAJE.megasPorImagen} * 1024`)
  })
})
