import { describe, expect, it } from 'vitest'
import { buscar, construirIndice, extracto, marcar } from './busqueda.js'
import { PAGINAS } from './contenido/index.js'

/** @type {import('./tipos.js').Pagina} */
const pagina = {
  id: 'automatizaciones',
  titulo: 'Automatizaciones',
  grupo: 'administracion',
  icono: '',
  entradilla: 'Reglas que trabajan solas.',
  secciones: [
    {
      titulo: 'Garantías',
      bloques: [{ tipo: 'filas', filas: [['Una sola vez', 'Índice único e `insertOrIgnore`.']] }],
    },
    {
      titulo: 'Su vida',
      bloques: [
        {
          tipo: 'recorrido',
          plano: {
            codigo: 'AU-02',
            titulo: 'Vida',
            nivel: 'C4 · 4',
            ancho: 100,
            alto: 100,
            nodos: [{ id: 'a', x: 0, y: 0, w: 10, h: 10, titulo: 'AplicarAutomatizaciones', tipo: 'Acciones' }],
          },
          pasos: [
            {
              titulo: 'Reservar',
              capa: 'datos',
              nodos: ['a'],
              aristas: [],
              archivo: 'Aplicar.php',
              texto: 'Inserta la ejecución.',
            },
          ],
        },
      ],
    },
  ],
}
const indice = construirIndice([pagina])

describe('buscador de la documentación', () => {
  it('encuentra filas, pasos y cajas de los planos, y dice a dónde llevan', () => {
    expect(buscar(indice, 'insertorignore')[0]).toMatchObject({
      titulo: 'Una sola vez',
      destino: { pagina: 'automatizaciones', seccion: 0, fila: 0 },
    })
    expect(buscar(indice, 'reservar')[0]).toMatchObject({ destino: { seccion: 1, paso: 0 } })
    expect(buscar(indice, 'aplicarautomatizaciones')[0]).toMatchObject({
      donde: 'Plano AU-02 · Vida',
      destino: { seccion: 1 },
    })
  })

  it('ignora tildes y mayúsculas, exige todas las palabras y pone primero lo que las tiene en el título', () => {
    expect(buscar(indice, 'GARANTIAS').map((e) => e.titulo)).toContain('Garantías')
    expect(buscar(indice, 'unico ejecucion')).toEqual([])
    expect(buscar(indice, 'una sola')[0].titulo).toBe('Una sola vez')
  })

  it('una consulta vacía no devuelve nada', () => {
    expect(buscar(indice, '   ')).toEqual([])
  })

  it('el texto se indexa sin las comillas invertidas que marcan código', () => {
    expect(buscar(indice, 'insertOrIgnore')[0].texto).toBe('Índice único e insertOrIgnore.')
  })

  it('marca los términos sobre el texto original, aunque la consulta no lleve tildes', () => {
    expect(marcar('Índice único', 'indice')).toEqual([
      { texto: 'Índice', marcado: true },
      { texto: ' único', marcado: false },
    ])
  })

  it('el extracto empieza cerca de la primera coincidencia', () => {
    const largo = `${'x'.repeat(100)} Reverb emite`
    expect(extracto(largo, 'reverb').startsWith('…')).toBe(true)
    expect(extracto(largo, 'reverb')).toContain('Reverb')
  })

  it('el índice real cubre todas las páginas', () => {
    const real = construirIndice(PAGINAS)
    expect(new Set(real.map((entrada) => entrada.destino.pagina))).toEqual(new Set(PAGINAS.map((p) => p.id)))
  })
})
