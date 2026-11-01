<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { REQUISITOS } from '../contrasena.js'

/** Qué le falta a la contraseña mientras se escribe: con ícono y texto, sin depender solo del color (RNF-F08). */
const props = defineProps({ contrasena: { type: String, default: '' } })

const requisitos = computed(() =>
  REQUISITOS.map(({ texto, cumple }) => ({ texto, cumplido: cumple(props.contrasena) })),
)
</script>

<template>
  <ul class="requisitos" aria-live="polite">
    <li
      v-for="requisito in requisitos"
      :key="requisito.texto"
      class="requisito"
      :class="{ 'requisito--cumplido': requisito.cumplido }"
    >
      <v-icon :icon="requisito.cumplido ? iconos.requisitoCumplido : iconos.requisitoPendiente" size="16" />
      {{ requisito.texto }}<span class="solo-lectores">{{ requisito.cumplido ? ': cumplido' : ': pendiente' }}</span>
    </li>
  </ul>
</template>

<style scoped>
.requisitos {
  display: grid;
  gap: 2px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.requisito {
  display: flex;
  gap: 6px;
  align-items: center;
  color: var(--suave);
  font-size: 12.5px;
}

.requisito--cumplido {
  color: var(--exito-texto);
}
</style>
