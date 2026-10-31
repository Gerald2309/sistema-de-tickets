<script setup>
/**
 * Interruptor de 32 × 18 px (sistema de diseño, Interruptor): un checkbox nativo con `role="switch"`, así que
 * el teclado, el foco y los lectores de pantalla funcionan sin código propio. Los de Vuetify son demasiado
 * grandes para una matriz de permisos.
 */
defineProps({
  etiqueta: { type: String, default: '' },
  deshabilitado: { type: Boolean, default: false },
})
const modelo = defineModel({ type: Boolean, default: false })
</script>

<template>
  <input
    v-model="modelo"
    type="checkbox"
    role="switch"
    class="interruptor"
    :aria-label="etiqueta || undefined"
    :disabled="deshabilitado"
  />
</template>

<style scoped>
.interruptor {
  position: relative;
  flex: none;
  width: 32px;
  height: 18px;
  margin: 0;
  background: var(--borde-fuerte);
  border-radius: 9px;
  cursor: pointer;
  transition: background-color 0.2s var(--curva);
  appearance: none;
}

.interruptor::after {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 14px;
  height: 14px;
  background: var(--superficie);
  border-radius: 50%;
  box-shadow: var(--sombra-1);
  transition: transform 0.25s var(--curva);
  content: '';
}

.interruptor:checked {
  background: var(--acento);
}

.interruptor:checked::after {
  transform: translateX(14px);
}

.interruptor:hover:not(:disabled) {
  filter: brightness(0.95);
}

.interruptor:focus-visible {
  outline: 2px solid var(--acento);
  outline-offset: 2px;
}

.interruptor:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>
