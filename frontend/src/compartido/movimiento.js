/** Curvas del sistema de diseño (las mismas de tokens.css), para la API Web Animations. */
export const CURVA = 'cubic-bezier(.22,1,.36,1)'
export const CURVA_ENTRADA_SALIDA = 'cubic-bezier(.76,0,.24,1)'

/**
 * @returns {boolean} la persona pidió reducir el movimiento en su sistema
 */
export function reducirMovimiento() {
  return typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true
}

/**
 * Directiva `v-revelar`: el título aparece letra a letra desde una máscara. Conserva el texto accesible
 * en `aria-label` y deja las letras ocultas para los lectores de pantalla (RNF-F11).
 * @type {import('vue').Directive<HTMLElement>}
 */
export const revelar = {
  mounted(elemento) {
    if (reducirMovimiento() || typeof elemento.animate !== 'function') {
      return
    }
    const texto = elemento.textContent ?? ''
    elemento.setAttribute('aria-label', texto)
    elemento.textContent = ''
    const palabras = texto.split(' ')
    palabras.forEach((palabra, indice) => {
      const envoltura = document.createElement('span')
      envoltura.className = 'revelar-palabra'
      envoltura.setAttribute('aria-hidden', 'true')
      for (const caracter of palabra) {
        const mascara = document.createElement('span')
        mascara.className = 'revelar-letra'
        const letra = document.createElement('span')
        letra.textContent = caracter
        mascara.appendChild(letra)
        envoltura.appendChild(mascara)
      }
      elemento.appendChild(envoltura)
      if (indice < palabras.length - 1) {
        elemento.appendChild(document.createTextNode(' '))
      }
    })
    elemento.querySelectorAll('.revelar-letra > span').forEach((letra, i) => {
      letra.animate([{ transform: 'translateY(115%)' }, { transform: 'none' }], {
        duration: 650,
        delay: i * 18,
        easing: CURVA,
        fill: 'backwards',
      })
    })
  },
}
