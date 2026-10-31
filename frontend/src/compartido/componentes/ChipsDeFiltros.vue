<script setup>
/** Un chip por filtro activo, con su ×; con dos o más, «Limpiar filtros» (sistema de diseño, Filtros). */
defineProps({
  filtros: {
    type: /** @type {import('vue').PropType<{ clave: string, etiqueta: string }[]>} */ (Array),
    required: true,
  },
})
defineEmits(['quitar', 'limpiar'])
</script>

<template>
  <TransitionGroup name="texto" tag="div" class="chips" aria-live="polite">
    <v-chip
      v-for="filtro in filtros"
      :key="filtro.clave"
      closable
      size="small"
      variant="tonal"
      color="primary"
      class="chips__chip"
      :close-label="`Quitar el filtro ${filtro.etiqueta}`"
      @click:close="$emit('quitar', filtro.clave)"
    >
      {{ filtro.etiqueta }}
    </v-chip>
    <button v-if="filtros.length > 1" key="limpiar" type="button" class="chips__limpiar" @click="$emit('limpiar')">
      Limpiar filtros
    </button>
  </TransitionGroup>
</template>

<style scoped>
.chips {
  display: contents;
}

.chips__chip {
  font-weight: 500;
}

.chips__limpiar {
  color: var(--acento);
  font: 500 13px var(--fuente);
  background: none;
  border: 0;
  cursor: pointer;
}

.chips__limpiar:hover {
  text-decoration: underline;
}
</style>
