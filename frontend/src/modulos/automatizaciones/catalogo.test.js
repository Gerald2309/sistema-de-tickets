import { describe, expect, it } from 'vitest'
import {
  aEspera,
  describirCamino,
  describirDisparo,
  disparoInicial,
  fraseDeLaAutomatizacion,
  nuevoPaso,
  operadoresDe,
  textoDelPaso,
  vistaPrevia,
} from './catalogo.js'

/** @type {import('./tipos.js').Nombres} */
const nombres = {
  empresa: (id) => ({ 1: 'Banco Aliado', 2: 'Transportes Sabana' })[id],
  persona: (id) => ({ 7: 'Diego Betancourt' })[id],
  rol: (id) => ({ 3: 'Supervisor' })[id],
}

describe('catálogo de automatizaciones', () => {
  it('la espera se lee en la unidad más grande que la expresa exacta', () => {
    expect(aEspera(4320)).toEqual({ cantidad: 3, unidad: 'dias' })
    expect(aEspera(240)).toEqual({ cantidad: 4, unidad: 'horas' })
    expect(aEspera(90)).toEqual({ cantidad: 90, unidad: 'minutos' })
  })

  it('cada disparador empieza con su medida y se describe con ella', () => {
    expect(describirDisparo(disparoInicial('sin_respuesta'))).toBe('Sin respuesta de soporte por 4 horas')
    expect(describirDisparo(disparoInicial('sla_por_vencer'))).toBe('Al 80 % del plazo de respuesta o más')
    expect(disparoInicial('creacion')).toEqual({
      disparador: 'creacion',
      espera_minutos: null,
      porcentaje: null,
      plazo: null,
    })
  })

  it('una condición se lee con los nombres de lo elegido', () => {
    const empresa = { ...nuevoPaso('condicion', 'empresa'), valores: [1, 2] }
    expect(textoDelPaso(empresa, nombres)).toBe('La empresa es Banco Aliado o Transportes Sabana')
    expect(textoDelPaso({ ...empresa, operador: 'no_es_ninguna_de' }, nombres)).toBe(
      'La empresa no es Banco Aliado ni Transportes Sabana',
    )
    expect(textoDelPaso({ ...nuevoPaso('condicion', 'palabras'), valores: ['vpn'] }, nombres)).toBe('Menciona «vpn»')
    expect(textoDelPaso(nuevoPaso('condicion', 'categoria'), nombres)).toBe('La categoría es … (elija al menos una)')
  })

  it('donde hay orden, la condición se lee como una comparación', () => {
    const prioridad = { ...nuevoPaso('condicion', 'prioridad'), operador: 'al_menos', valores: ['alta'] }
    expect(textoDelPaso(/** @type {any} */ (prioridad), nombres)).toBe('La prioridad es al menos alta')
    expect(textoDelPaso(nuevoPaso('condicion', 'plazo_respuesta_consumido'), nombres)).toBe(
      'El plazo de respuesta consumido es al menos el 80 %',
    )
    const igual = { ...nuevoPaso('condicion', 'plazo_resolucion_consumido'), operador: 'igual', valores: [100] }
    expect(textoDelPaso(/** @type {any} */ (igual), nombres)).toBe('El plazo de resolución consumido es el 100 %')
  })

  it('cada campo ofrece solo las comparaciones que tienen sentido', () => {
    const valores = (/** @type {string} */ campo) => operadoresDe(campo).map((operador) => operador.valor)
    expect(valores('empresa')).toEqual(['es_una_de', 'no_es_ninguna_de'])
    expect(valores('prioridad')).toEqual(['es_una_de', 'no_es_ninguna_de', 'al_menos', 'como_maximo'])
    expect(valores('plazo_respuesta_consumido')).toEqual(['al_menos', 'como_maximo', 'igual'])
  })

  it('el flujo entero se lee en una frase, con las ramas entre paréntesis', () => {
    const pasos = [
      {
        ...nuevoPaso('condicion', 'categoria'),
        valores: ['red'],
        si: [{ ...nuevoPaso('accion', 'asignar'), usuario_id: 7 }],
        no: [{ ...nuevoPaso('accion', 'avisar'), id: 3 }],
      },
    ]
    expect(fraseDeLaAutomatizacion(disparoInicial('creacion'), pasos, nombres)).toBe(
      'Cuando se crea un incidente: si la categoría es Red: asignar a Diego Betancourt; si no: avisar al rol Supervisor.',
    )
  })

  it('la vista previa rellena las variables conocidas y deja las demás', () => {
    expect(vistaPrevia('Hola {nombre}, caso {número} {otra}')).toBe('Hola Marta Ruiz, caso INC-001024 {otra}')
  })

  it('el camino de una prueba se lee condición por condición', () => {
    expect(
      describirCamino([
        { campo: 'categoria', cumple: true },
        { campo: 'empresa', cumple: false },
      ]),
    ).toBe('Categoría sí → Empresa no')
    expect(describirCamino([])).toBe('Sin condiciones')
  })
})
