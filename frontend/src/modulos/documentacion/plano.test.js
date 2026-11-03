import { describe, expect, it } from 'vitest'
import { PAGINAS } from './contenido/index.js'
import { idDeArista, trazar } from './plano.js'

/** @param {Partial<import('./tipos.js').Nodo>} caja */
const caja = (caja) => ({ id: 'x', titulo: 'x', x: 0, y: 0, w: 100, h: 50, ...caja })

describe('trazar', () => {
  it('une cajas apiladas con una línea vertical recta por el centro de lo que comparten', () => {
    expect(trazar(caja({ x: 0, y: 0 }), caja({ x: 20, y: 100 })).d).toBe('M60 50V100')
    expect(trazar(caja({ x: 0, y: 100 }), caja({ x: 0, y: 0 })).d).toBe('M50 100V50')
  })

  it('une cajas lado a lado con una línea horizontal recta', () => {
    expect(trazar(caja({ x: 0, y: 0 }), caja({ x: 200, y: 10 })).d).toBe('M100 30H200')
    expect(trazar(caja({ x: 200, y: 0 }), caja({ x: 0, y: 0 })).d).toBe('M200 25H100')
  })

  it('si no comparten franja baja, cruza y vuelve a bajar, llegando dentro de la caja de destino', () => {
    expect(trazar(caja({ x: 0, y: 0 }), caja({ x: 300, y: 200 })).d).toBe('M50 50V125H318V200')
  })

  it('con modo h sale de costado y gira en la columna pedida', () => {
    const trazo = trazar(caja({ x: 0, y: 0 }), caja({ x: 0, y: 200 }), { modo: 'h', mx: 150 })
    expect(trazo.d).toBe('M100 25H150V225H100')
  })
})

describe('planos de la documentación', () => {
  const planos = PAGINAS.flatMap((pagina) =>
    pagina.secciones.flatMap((seccion) =>
      seccion.bloques.flatMap((bloque) =>
        'plano' in bloque ? [bloque.plano] : bloque.tipo === 'planos' ? bloque.vistas.map((vista) => vista.plano) : [],
      ),
    ),
  )

  it('cada flecha une cajas que existen', () => {
    const rotas = planos.flatMap((plano) => {
      const ids = new Set(plano.nodos.map((nodo) => nodo.id))
      return (plano.aristas ?? [])
        .filter((arista) => !ids.has(arista.de) || !ids.has(arista.a))
        .map((a) => `${plano.codigo} ${idDeArista(a)}`)
    })
    expect(rotas).toEqual([])
  })

  it('los pasos y los focos solo iluminan cajas y flechas de su plano', () => {
    const rotos = PAGINAS.flatMap((pagina) =>
      pagina.secciones.flatMap((seccion) =>
        seccion.bloques.flatMap((bloque) => {
          if (bloque.tipo !== 'recorrido' && bloque.tipo !== 'focos') return []
          const nodos = new Set(bloque.plano.nodos.map((nodo) => nodo.id))
          const aristas = new Set((bloque.plano.aristas ?? []).map(idDeArista))
          const lecturas = bloque.tipo === 'recorrido' ? bloque.pasos : bloque.focos
          return lecturas.flatMap((lectura) => [
            ...lectura.nodos.filter((nodo) => !nodos.has(nodo)),
            ...lectura.aristas.filter((arista) => !aristas.has(arista)),
          ])
        }),
      ),
    )
    expect(rotos).toEqual([])
  })

  it('cada plano tiene un código propio', () => {
    const codigos = [...new Set(planos)].map((plano) => plano.codigo)
    expect(new Set(codigos).size).toBe(codigos.length)
  })
})
