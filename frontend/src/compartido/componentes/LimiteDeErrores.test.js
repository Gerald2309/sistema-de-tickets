import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'
import { crearVuetify } from '@/nucleo/plugins/vuetify.js'
import LimiteDeErrores from './LimiteDeErrores.vue'

/** Una pantalla que falla al montarse las primeras `fallos` veces. */
function pantallaQueFalla(fallos = 1) {
  let montajes = 0
  return defineComponent({
    setup() {
      montajes += 1
      if (montajes <= fallos) {
        throw new Error('Fallo inesperado')
      }
      return () => h('p', 'Pantalla en pie')
    },
  })
}

const montar = (/** @type {import('vue').Component} */ pantalla) =>
  mount(LimiteDeErrores, { slots: { default: () => h(pantalla) }, global: { plugins: [crearVuetify()] } })

describe('límite de errores (RNF-F25)', () => {
  it('un fallo de la pantalla muestra un mensaje con «Reintentar» en lugar de propagarse', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    const envoltorio = montar(pantallaQueFalla())
    await envoltorio.vm.$nextTick()

    expect(envoltorio.text()).toContain('Esta pantalla tuvo un problema')
    expect(envoltorio.get('[role="alert"]').exists()).toBe(true)
  })

  it('«Reintentar» vuelve a montar la pantalla desde cero', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {})
    const envoltorio = montar(pantallaQueFalla(1))
    await envoltorio.vm.$nextTick()

    await envoltorio.get('button').trigger('click')

    expect(envoltorio.text()).toContain('Pantalla en pie')
    expect(envoltorio.text()).not.toContain('Esta pantalla tuvo un problema')
  })
})
