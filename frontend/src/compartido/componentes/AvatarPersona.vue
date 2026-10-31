<script setup>
import { computed } from 'vue'

/** Persona con sus iniciales de dos letras (sistema de diseño, Estado y datos). */
const props = defineProps({
  nombre: { type: String, default: '' },
  tamano: { type: Number, default: 22 },
  conNombre: { type: Boolean, default: true },
})
const iniciales = computed(() =>
  props.nombre
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0]?.toUpperCase())
    .join(''),
)
</script>

<template>
  <span class="persona">
    <v-avatar :size="tamano" class="persona__avatar" aria-hidden="true">{{ iniciales }}</v-avatar>
    <span v-if="conNombre">{{ nombre }}</span>
  </span>
</template>

<style scoped>
.persona {
  display: inline-flex;
  gap: 8px;
  align-items: center;
  white-space: nowrap;
}

.persona__avatar {
  color: var(--acento-texto);
  font-size: 10px;
  font-weight: 700;
  background: var(--acento-fondo);
}
</style>
