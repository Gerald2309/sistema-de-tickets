<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * La línea entre dos pasos, con su «+» para añadir uno ahí. Mientras se elige el bloque, en su lugar se ve el
 * hueco donde quedará.
 */
defineProps({ eligiendo: { type: Boolean, default: false } })
defineEmits(['anadir'])
</script>

<template>
  <div class="union">
    <button type="button" class="union__mas" aria-label="Añadir un paso aquí" @click="$emit('anadir')">
      <v-icon :icon="iconos.crear" size="14" />
    </button>
  </div>
  <template v-if="eligiendo">
    <div class="hueco"><v-icon :icon="iconos.flecha" size="18" />Elija un bloque en el panel</div>
    <div class="union" />
  </template>
</template>

<style scoped>
.union {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 52px;
}

.union::before {
  position: absolute;
  top: 0;
  bottom: 6px;
  width: 1px;
  background: var(--borde-fuerte);
  content: '';
}

.union::after {
  position: absolute;
  bottom: 1px;
  border: 5px solid transparent;
  border-top: 6px solid var(--borde-fuerte);
  border-bottom: 0;
  content: '';
}

.union__mas {
  position: relative;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 24px;
  height: 24px;
  color: var(--suave);
  background: var(--superficie);
  border: 1px solid var(--borde-fuerte);
  border-radius: 50%;
  cursor: pointer;
  transition:
    color var(--rapida),
    border-color var(--rapida);
}

.union__mas:hover,
.union__mas:focus-visible {
  color: var(--acento-texto);
  border-color: var(--acento);
}

.hueco {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: center;
  width: 290px;
  padding: 16px;
  color: var(--acento-texto);
  font-weight: 600;
  background: var(--acento-fondo);
  border: 1.5px dashed var(--acento);
  border-radius: var(--radio);
}
</style>
