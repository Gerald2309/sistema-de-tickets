/** @type {WeakMap<HTMLElement, ResizeObserver>} */
const observadores = new WeakMap()

/**
 * Directiva `v-desplazable="'Registros de auditoría'"`: una tabla que no cabe se desplaza de lado, y quien
 * usa el teclado debe poder alcanzar esa zona (WCAG 2.1.1, regla `scrollable-region-focusable`). La zona
 * solo entra en el orden de tabulación mientras de verdad se desplaza, para no sumar paradas inútiles.
 * Sirve para `v-table` (envuelve su contenido en `.v-table__wrapper`) o para cualquier contenedor.
 * @type {import('vue').Directive<HTMLElement, string>}
 */
export const desplazable = {
  mounted(elemento, { value: nombre }) {
    const zona = /** @type {HTMLElement} */ (elemento.querySelector('.v-table__wrapper') ?? elemento)
    zona.setAttribute('role', 'region')
    zona.setAttribute('aria-label', nombre)
    const actualizar = () => {
      if (zona.scrollWidth > zona.clientWidth) {
        zona.tabIndex = 0
      } else {
        zona.removeAttribute('tabindex')
      }
    }
    actualizar()
    if (typeof ResizeObserver === 'function') {
      const observador = new ResizeObserver(actualizar)
      observador.observe(zona)
      zona.firstElementChild && observador.observe(zona.firstElementChild)
      observadores.set(elemento, observador)
    }
  },
  unmounted(elemento) {
    observadores.get(elemento)?.disconnect()
    observadores.delete(elemento)
  },
}
