<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * Panel que entra por la derecha para crear o editar sin salir de la lista (sistema de diseño, Panel lateral).
 * El contenido va en la ranura por defecto y los botones en `pie`.
 */
defineProps({ titulo: { type: String, required: true } })
const abierto = defineModel({ type: Boolean, default: false })
</script>

<template>
  <v-navigation-drawer
    v-model="abierto"
    location="right"
    temporary
    width="440"
    class="panel-lateral"
    :aria-label="titulo"
  >
    <div class="panel-lateral__contenido">
      <header class="panel-lateral__cabecera">
        <h2 class="panel-lateral__titulo">{{ titulo }}</h2>
        <v-btn :icon="iconos.cerrar" variant="text" size="small" aria-label="Cerrar" @click="abierto = false" />
      </header>
      <div class="panel-lateral__cuerpo"><slot /></div>
      <footer v-if="$slots.pie" class="panel-lateral__pie"><slot name="pie" /></footer>
    </div>
  </v-navigation-drawer>
</template>

<style scoped>
.panel-lateral__contenido {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.panel-lateral__cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 14px 20px;
  border-bottom: 1px solid var(--borde);
}

.panel-lateral__titulo {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
}

.panel-lateral__cuerpo {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.panel-lateral__pie {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  padding: 12px 20px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}
</style>
