<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'

/** Descargar el listado con los filtros activos (RF-F25). Mientras descarga, el botón muestra el avance. */
const props = defineProps({
  descargando: { type: Boolean, default: false },
  progreso: { type: /** @type {import('vue').PropType<number | null>} */ (Number), default: null },
})
const emit = defineEmits(['descargar'])

const texto = computed(() => {
  if (!props.descargando) {
    return 'Descargar'
  }
  return props.progreso === null ? 'Preparando…' : `Descargando ${Math.round(props.progreso * 100)} %`
})
</script>

<template>
  <v-menu :disabled="descargando">
    <template #activator="{ props: activador }">
      <v-btn
        v-bind="activador"
        variant="outlined"
        :prepend-icon="iconos.descargar"
        :append-icon="descargando ? undefined : iconos.desplegar"
      >
        <span aria-live="polite">{{ texto }}</span>
        <template v-if="descargando" #append>
          <v-progress-circular
            :model-value="(progreso ?? 0) * 100"
            :indeterminate="progreso === null"
            size="16"
            width="2"
            aria-hidden="true"
          />
        </template>
      </v-btn>
    </template>
    <v-list min-width="200">
      <v-list-subheader>Con los filtros activos</v-list-subheader>
      <v-list-item :prepend-icon="iconos.csv" title="CSV" @click="emit('descargar', 'csv')" />
      <v-list-item :prepend-icon="iconos.excel" title="Excel (.xlsx)" @click="emit('descargar', 'xlsx')" />
    </v-list>
  </v-menu>
</template>
