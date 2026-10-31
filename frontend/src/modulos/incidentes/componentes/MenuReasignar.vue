<script setup>
import { computed } from 'vue'
import { agruparPorRol, useBuscadorDePersonas } from '@/modulos/identidad/index.js'

/**
 * Buscador de la persona a quien reasignar, en lote o desde el detalle. Vive dentro del menú, que se monta al
 * abrirse: la lista de personas solo se pide si alguien va a reasignar. Solo aparecen quienes atienden
 * incidentes, agrupados por nivel. `quitable` ofrece dejarlo sin asignar.
 */
defineProps({ quitable: { type: Boolean, default: false } })
const emit = defineEmits(['elegir', 'quitar'])
const personas = useBuscadorDePersonas({ paraAsignar: true })
const opciones = computed(() => agruparPorRol(personas.personas.value))

/** @param {string | null} texto */
const buscar = (texto) => (personas.texto.value = texto ?? '')
/** @param {number | null} id */
const elegir = (id) => id && emit('elegir', id)
</script>

<template>
  <v-card class="reasignar" min-width="280">
    <v-autocomplete
      :items="opciones"
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
    <v-btn v-if="quitable" variant="text" size="small" class="mt-2" block @click="emit('quitar')"
      >Dejar sin asignar</v-btn
    >
  </v-card>
</template>

<style scoped>
.reasignar {
  padding: 12px;
  border: 1px solid var(--borde);
}
</style>
