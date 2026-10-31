<script setup>
import { useBuscadorDePersonas } from '@/modulos/identidad/index.js'

/**
 * Buscador de la persona a quien reasignar en lote. Vive dentro del menú, que se monta al abrirse: la lista de
 * personas solo se pide si alguien va a reasignar.
 */
const emit = defineEmits(['elegir'])
const personas = useBuscadorDePersonas()

/** @param {string | null} texto */
const buscar = (texto) => (personas.texto.value = texto ?? '')
/** @param {number | null} id */
const elegir = (id) => id && emit('elegir', id)
</script>

<template>
  <v-card class="reasignar" min-width="280">
    <v-autocomplete
      :items="personas.personas.value"
      item-title="nombre"
      item-value="id"
      label="Asignar a"
      autofocus
      hide-details
      no-filter
      :loading="personas.cargando.value"
      no-data-text="Escriba un nombre"
      @update:search="buscar"
      @update:model-value="elegir"
    />
  </v-card>
</template>

<style scoped>
.reasignar {
  padding: 12px;
  border: 1px solid var(--borde);
}
</style>
