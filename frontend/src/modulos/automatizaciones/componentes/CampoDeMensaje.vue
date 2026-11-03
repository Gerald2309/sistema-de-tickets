<script setup>
import { nextTick, ref } from 'vue'
import { VARIABLES, vistaPrevia } from '../catalogo.js'

/** Un mensaje con datos del incidente entre llaves, y cómo se leería con un incidente de ejemplo. */
defineProps({
  etiqueta: { type: String, required: true },
  etiquetaDeLaVista: { type: String, default: 'Así lo verá el solicitante' },
})
const modelo = defineModel({ type: String, required: true })
const campo = ref(/** @type {import('vue').ComponentPublicInstance | null} */ (null))

/** @param {string} variable */
const conLlaves = (variable) => `{${variable}}`

/**
 * Inserta la variable donde está el cursor (con un espacio si quedaría pegada a la palabra anterior) y deja
 * el cursor detrás de ella para seguir escribiendo.
 * @param {string} variable
 */
async function insertar(variable) {
  const area = /** @type {HTMLTextAreaElement | null} */ (campo.value?.$el.querySelector('textarea'))
  const inicio = area?.selectionStart ?? modelo.value.length
  const fin = area?.selectionEnd ?? inicio
  const antes = modelo.value.slice(0, inicio)
  const insertado = `${antes && !/\s$/.test(antes) ? ' ' : ''}${conLlaves(variable)}`
  modelo.value = antes + insertado + modelo.value.slice(fin)
  await nextTick()
  area?.focus()
  area?.setSelectionRange(inicio + insertado.length, inicio + insertado.length)
}
</script>

<template>
  <div>
    <v-textarea ref="campo" v-model="modelo" :label="etiqueta" rows="4" auto-grow counter="5000" maxlength="5000" />
    <div class="variables">
      <span>Insertar:</span>
      <!-- mousedown.prevent: el clic no le quita el foco al mensaje, así se sabe dónde está el cursor. -->
      <button
        v-for="variable in VARIABLES"
        :key="variable"
        type="button"
        class="variable"
        @mousedown.prevent
        @click="insertar(variable)"
      >
        {{ conLlaves(variable) }}
      </button>
    </div>
    <p class="rotulo">{{ etiquetaDeLaVista }}</p>
    <p class="vista">{{ vistaPrevia(modelo) || '—' }}</p>
  </div>
</template>

<style scoped>
.variables {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  margin: -8px 0 16px;
  color: var(--tenue);
  font-size: 12px;
}

.variable {
  padding: 2px 8px;
  color: var(--suave);
  font: inherit;
  background: var(--fondo);
  border: 1px solid var(--borde);
  border-radius: var(--radio-chip);
  cursor: pointer;
}

.variable:hover,
.variable:focus-visible {
  color: var(--acento-texto);
  border-color: var(--acento);
}

.rotulo {
  margin: 0 0 4px;
  color: var(--tenue);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.vista {
  margin: 0 0 16px;
  padding: 12px;
  color: var(--suave);
  font-size: 13px;
  white-space: pre-wrap;
  background: var(--fondo);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}
</style>
