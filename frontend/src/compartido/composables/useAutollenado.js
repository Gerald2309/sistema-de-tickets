import { reactive } from 'vue'

/**
 * Campos que el navegador rellenó solo (correo y contraseña guardados). Chrome muestra lo rellenado pero no se lo
 * entrega a la página hasta que la persona interactúa, así que el campo parece vacío: la etiqueta queda encima del
 * texto y el valor no llega al enviar. La animación que dispara `:-webkit-autofill` (base.css) avisa cuál se
 * rellenó, para subir su etiqueta; al enviar, `leer` toma el valor que ya muestra el campo.
 */
export function useAutollenado() {
  /** @type {Set<string>} nombres (`name`) de los campos rellenados por el navegador */
  const rellenados = reactive(new Set())

  /** @param {AnimationEvent} evento escuchado en el formulario: la animación sube desde cada campo */
  function alAnimar(evento) {
    if (evento.animationName === 'autollenado' && evento.target instanceof HTMLInputElement) {
      rellenados.add(evento.target.name)
    }
  }

  /**
   * @param {HTMLElement | undefined} formulario
   * @param {string} nombre
   * @returns {string} lo que muestra el campo, o vacío si no existe
   */
  function leer(formulario, nombre) {
    const campo = formulario?.querySelector(`input[name="${nombre}"]`)
    return campo instanceof HTMLInputElement ? campo.value : ''
  }

  return { rellenados, alAnimar, leer }
}
