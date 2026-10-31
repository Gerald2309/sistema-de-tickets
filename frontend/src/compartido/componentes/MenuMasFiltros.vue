<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * «Más filtros»: los filtros menos usados de una lista, en un menú que no se cierra al elegir (sistema de diseño,
 * Barra de herramientas de listas). Los campos van en la ranura.
 */
</script>

<template>
  <v-menu :close-on-content-click="false" location="bottom start">
    <template #activator="{ props: activador }">
      <button type="button" class="filtro" v-bind="activador">
        <v-icon :icon="iconos.masFiltros" size="16" />Más filtros
      </button>
    </template>
    <v-card class="mas-filtros" min-width="320">
      <v-card-text class="mas-filtros__cuerpo"><slot /></v-card-text>
    </v-card>
  </v-menu>
</template>

<style scoped>
.mas-filtros {
  border: 1px solid var(--borde);
  box-shadow: var(--sombra-2) !important;
}

.mas-filtros__cuerpo {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
</style>
