<script setup>
import { iconos } from '@/compartido/iconos.js'

/** Filtro como texto con flecha, sin recuadro (sistema de diseño, Barra de herramientas de listas). */
defineProps({
  etiqueta: { type: String, required: true },
  opciones: {
    type: /** @type {import('vue').PropType<{ valor: string, nombre: string, color?: string, icono?: string }[]>} */ (
      Array
    ),
    required: true,
  },
})
const modelo = defineModel({ type: String, default: undefined })
</script>

<template>
  <v-menu>
    <template #activator="{ props: activador }">
      <button type="button" class="filtro" v-bind="activador" :aria-label="`Filtrar por ${etiqueta.toLowerCase()}`">
        {{ etiqueta }}<v-icon :icon="iconos.desplegar" size="16" />
      </button>
    </template>
    <v-list min-width="190">
      <v-list-item
        v-for="opcion in opciones"
        :key="opcion.valor"
        :title="opcion.nombre"
        :active="modelo === opcion.valor"
        @click="modelo = opcion.valor"
      >
        <template #prepend>
          <span v-if="opcion.color && !opcion.icono" class="filtro__punto" :style="{ background: opcion.color }" />
          <v-icon
            v-else-if="opcion.icono"
            :icon="opcion.icono"
            size="16"
            :style="{ color: opcion.color }"
            class="mr-3"
          />
        </template>
      </v-list-item>
    </v-list>
  </v-menu>
</template>

<style scoped>
.filtro__punto {
  width: 8px;
  height: 8px;
  margin-right: 12px;
  border-radius: 50%;
}
</style>
