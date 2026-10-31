<script setup>
/** Esqueleto de carga: nunca una pantalla en blanco (sistema de diseño, Avisos). */
defineProps({
  ancho: { type: String, default: '100%' },
  alto: { type: String, default: '12px' },
})
</script>

<template>
  <span class="esqueleto" :style="{ width: ancho, height: alto }" aria-hidden="true" />
</template>

<style scoped>
.esqueleto {
  display: block;
  background: linear-gradient(90deg, var(--resalte) 25%, var(--acento-fondo) 50%, var(--resalte) 75%);
  background-size: 200% 100%;
  border-radius: 6px;
  animation: brilla 1.4s linear infinite;
}
</style>
