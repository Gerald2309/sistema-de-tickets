<script setup>
import { computed } from 'vue'
import { agruparPorRol, useBuscadorDePersonas } from '@/modulos/identidad/index.js'

/** Elegir una persona escribiendo su nombre. Para asignar, solo quienes atienden incidentes, por rol. */
const props = defineProps({
  etiqueta: { type: String, required: true },
  paraAsignar: { type: Boolean, default: false },
})
const modelo = defineModel({ type: /** @type {import('vue').PropType<number | null>} */ (Number), default: null })

const { texto, personas, cargando } = useBuscadorDePersonas({
  paraAsignar: props.paraAsignar,
  elegidos: () => (modelo.value ? [modelo.value] : []),
})
const opciones = computed(() => (props.paraAsignar ? agruparPorRol(personas.value) : personas.value))
</script>

<template>
  <v-autocomplete
    v-model="modelo"
    v-model:search="texto"
    :label="etiqueta"
    :items="opciones"
    item-title="nombre"
    item-value="id"
    :loading="cargando"
    no-filter
  />
</template>
