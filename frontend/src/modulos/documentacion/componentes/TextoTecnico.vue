<script setup>
import { computed } from 'vue'
import { enlaceAlCodigo } from '../repositorio.js'

/**
 * Texto con fragmentos de código: lo que va entre comillas invertidas se muestra como `código`. Si es la ruta
 * de un archivo del repositorio, enlaza a ese archivo en GitHub.
 */
const props = defineProps({ texto: { type: String, required: true } })

const tramos = computed(() =>
  props.texto.split('`').map((contenido, i) => ({
    contenido,
    esCodigo: i % 2 === 1,
    enlace: i % 2 === 1 && contenido.includes('/') ? enlaceAlCodigo(contenido) : null,
  })),
)
</script>

<template>
  <span
    ><template v-for="(tramo, i) in tramos" :key="i"
      ><a v-if="tramo.enlace" :href="tramo.enlace" target="_blank" rel="noopener" class="enlace-al-codigo"
        ><code class="codigo-en-linea">{{ tramo.contenido }}</code></a
      ><code v-else-if="tramo.esCodigo" class="codigo-en-linea">{{ tramo.contenido }}</code
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

.enlace-al-codigo {
  text-decoration: none;
}

.enlace-al-codigo .codigo-en-linea {
  border-color: var(--acento-borde);
  text-decoration: underline;
  text-decoration-color: var(--acento-borde);
  text-underline-offset: 2px;
}

.enlace-al-codigo:hover .codigo-en-linea {
  background: var(--acento-fondo);
}
</style>
