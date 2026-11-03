<script setup>
import { computed } from 'vue'

/** Texto con fragmentos de código: lo que va entre comillas invertidas se muestra como `código`. */
const props = defineProps({ texto: { type: String, required: true } })

const tramos = computed(() => props.texto.split('`').map((contenido, i) => ({ contenido, esCodigo: i % 2 === 1 })))
</script>

<template>
  <span
    ><template v-for="(tramo, i) in tramos" :key="i"
      ><code v-if="tramo.esCodigo" class="codigo-en-linea">{{ tramo.contenido }}</code
      ><template v-else>{{ tramo.contenido }}</template></template
    ></span
  >
</template>

<style scoped>
.codigo-en-linea {
  padding: 1px 4px;
  color: var(--acento-texto);
  font: 12.5px var(--mono);
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio-chip);
}
</style>
