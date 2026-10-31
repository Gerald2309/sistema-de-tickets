<script setup>
import { computed } from 'vue'
import { presentarEstado } from '../presentacion.js'

/** Estado: punto de color y texto, nunca solo color (RNF-F08). En caja, para el detalle. */
const props = defineProps({
  estado: { type: String, required: true },
  caja: { type: Boolean, default: false },
})
const presentacion = computed(() => presentarEstado(props.estado))
</script>

<template>
  <span class="estado" :class="{ 'estado--caja': caja }">
    <span class="estado__punto" :style="{ background: presentacion.color }" aria-hidden="true" />
    <Transition name="texto" mode="out-in">
      <span :key="estado">{{ presentacion.nombre }}</span>
    </Transition>
  </span>
</template>

<style scoped>
.estado {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  white-space: nowrap;
}

.estado--caja {
  height: 24px;
  padding: 0 9px;
  font-size: 12.5px;
  font-weight: 500;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.estado__punto {
  flex: none;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  transition: background-color 0.5s var(--curva);
}
</style>
