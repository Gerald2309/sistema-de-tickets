<script setup>
import { computed } from 'vue'
import { presentarPrioridad } from '../presentacion.js'

/** Prioridad: flecha que sube con la urgencia, con color y texto (RNF-F08). */
const props = defineProps({
  prioridad: { type: String, required: true },
  caja: { type: Boolean, default: false },
})
const presentacion = computed(() => presentarPrioridad(props.prioridad))
</script>

<template>
  <span class="prioridad" :class="{ 'insignia-caja': caja }">
    <v-icon :icon="presentacion.icono" size="16" :style="{ color: presentacion.color }" aria-hidden="true" />
    {{ presentacion.nombre }}
  </span>
</template>

<style scoped>
.prioridad {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  white-space: nowrap;
}
</style>
