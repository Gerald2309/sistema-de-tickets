<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { iconos } from '@/compartido/iconos.js'

/**
 * Búsqueda de una lista: avisa el texto tras dejar de escribir (300 ms) para no consultar en cada tecla
 * (RF-F21, RNF-F04). Un error de la API (por ejemplo, sin palabras útiles) aparece bajo el campo.
 */
const props = defineProps({
  modelValue: { type: String, default: '' },
  etiqueta: { type: String, required: true },
  error: { type: String, default: '' },
  espera: { type: Number, default: 300 },
})
const emit = defineEmits(['update:modelValue'])

const texto = ref(props.modelValue)
/** @type {ReturnType<typeof setTimeout> | undefined} */
let temporizador

watch(
  () => props.modelValue,
  (valor) => {
    if (valor !== texto.value) {
      texto.value = valor
    }
  },
)

/** @param {string} valor */
function alEscribir(valor) {
  texto.value = valor
  clearTimeout(temporizador)
  temporizador = setTimeout(() => emit('update:modelValue', valor.trim()), props.espera)
}

onBeforeUnmount(() => clearTimeout(temporizador))
</script>

<template>
  <div class="buscador-con-error">
    <label class="buscador" :class="{ 'buscador--error': error }">
      <v-icon :icon="iconos.buscar" size="18" aria-hidden="true" />
      <input
        :value="texto"
        type="search"
        :placeholder="etiqueta"
        :aria-label="etiqueta"
        :aria-invalid="!!error"
        maxlength="100"
        @input="alEscribir(/** @type {HTMLInputElement} */ ($event.target).value)"
        @keydown.enter.prevent="emit('update:modelValue', texto.trim())"
      />
    </label>
    <p v-if="error" class="buscador__error" role="alert">{{ error }}</p>
  </div>
</template>

<style scoped>
.buscador-con-error {
  flex: 0 1 300px;
  min-width: 180px;
}

.buscador {
  display: flex;
  gap: 8px;
  align-items: center;
  height: 34px;
  padding: 0 10px;
  color: var(--suave);
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  cursor: text;
  transition:
    border-color var(--rapida),
    box-shadow var(--rapida);
}

.buscador:focus-within {
  border-color: var(--acento);
  box-shadow: var(--anillo-foco);
}

.buscador--error {
  border-color: var(--error);
}

.buscador input {
  flex: 1;
  min-width: 0;
  color: var(--texto);
  font: 400 13.5px var(--fuente);
  background: transparent;
  border: 0;
  outline: 0;
}

.buscador__error {
  margin: 4px 0 0;
  color: var(--error);
  font-size: 12px;
}
</style>
