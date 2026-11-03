/**
 * Geometría de los planos: flechas en ángulo recto, como en un dibujo técnico.
 */

/** @typedef {import('./tipos.js').Nodo} Nodo */
/** @typedef {{ d: string, x: number, y: number, ancla: 'start' | 'middle' }} Trazo trayecto SVG y lugar de la etiqueta */

// Lo mínimo que dos cajas deben compartir de franja para unirlas con una línea recta.
const SOLAPE_MINIMO = 10
// Distancia al borde de la caja de destino a la que llega una flecha que gira.
const MARGEN_DE_LLEGADA = 18

/**
 * @param {Nodo} caja
 * @returns {{ izquierda: number, derecha: number, arriba: number, abajo: number }}
 */
const bordes = (caja) => ({ izquierda: caja.x, derecha: caja.x + caja.w, arriba: caja.y, abajo: caja.y + caja.h })

/**
 * Cajas apiladas: línea vertical recta. Cajas lado a lado: línea horizontal recta. Si no comparten franja,
 * baja (o sube), cruza y vuelve a bajar. Con `modo: 'h'` sale de costado y gira en la columna `mx`, para
 * esquivar las cajas que quedan en medio.
 * @param {Nodo} origen
 * @param {Nodo} destino
 * @param {{ modo?: 'h', mx?: number }} [opciones]
 * @returns {Trazo}
 */
export function trazar(origen, destino, { modo, mx } = {}) {
  const o = bordes(origen)
  const d = bordes(destino)

  if (modo === 'h' && mx !== undefined) {
    const sy = origen.y + origen.h / 2
    const ty = destino.y + destino.h / 2
    const sx = mx >= o.derecha ? o.derecha : o.izquierda
    const tx = mx >= d.derecha ? d.derecha : d.izquierda
    return { d: `M${sx} ${sy}H${mx}V${ty}H${tx}`, x: (mx + tx) / 2, y: ty - 6, ancla: 'middle' }
  }

  const solapeX = [Math.max(o.izquierda, d.izquierda), Math.min(o.derecha, d.derecha)]
  if (solapeX[0] < solapeX[1] - SOLAPE_MINIMO) {
    const x = (solapeX[0] + solapeX[1]) / 2
    const baja = d.arriba >= o.abajo
    const sy = baja ? o.abajo : o.arriba
    const ty = baja ? d.arriba : d.abajo
    return { d: `M${x} ${sy}V${ty}`, x: x + 6, y: (sy + ty) / 2 + 4, ancla: 'start' }
  }

  const solapeY = [Math.max(o.arriba, d.arriba), Math.min(o.abajo, d.abajo)]
  if (solapeY[0] < solapeY[1] - SOLAPE_MINIMO) {
    const y = (solapeY[0] + solapeY[1]) / 2
    const aLaDerecha = d.izquierda >= o.derecha
    const sx = aLaDerecha ? o.derecha : o.izquierda
    const tx = aLaDerecha ? d.izquierda : d.derecha
    return { d: `M${sx} ${y}H${tx}`, x: (sx + tx) / 2, y: y - 6, ancla: 'middle' }
  }

  const baja = d.arriba >= o.abajo
  const sy = baja ? o.abajo : o.arriba
  const ty = baja ? d.arriba : d.abajo
  const my = (sy + ty) / 2
  const sx = origen.x + origen.w / 2
  const tx = Math.min(Math.max(sx, d.izquierda + MARGEN_DE_LLEGADA), d.derecha - MARGEN_DE_LLEGADA)
  return { d: `M${sx} ${sy}V${my}H${tx}V${ty}`, x: (sx + tx) / 2, y: my - 6, ancla: 'middle' }
}

/**
 * @param {import('./tipos.js').Arista} arista
 * @returns {string} identificador «origen>destino», el que usan los pasos y los focos
 */
export const idDeArista = (arista) => `${arista.de}>${arista.a}`
