<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaHora, tiempoRelativo } from '@/compartido/presentacion/fechas.js'

/**
 * Vencimiento con una sola señal (sistema de diseño): reloj y texto rojo si venció; ámbar si vence en 3 horas
 * o menos; gris si falta más. «Vencido» lo decide la API (campo `vencido`); aquí solo se presenta.
 */
const props = defineProps({
  fecha: { type: String, required: true },
  vencido: { type: Boolean, default: false },
  // Resueltos y cerrados no vencen: no se muestra plazo.
  pendiente: { type: Boolean, default: true },
  ahora: { type: Date, default: () => new Date() },
})

const UMBRAL_URGENTE_MS = 3 * 60 * 60 * 1000

const relativo = computed(() => tiempoRelativo(props.fecha, props.ahora))
const urgente = computed(() => !props.vencido && relativo.value.milisegundos <= UMBRAL_URGENTE_MS)
const titulo = computed(() => fechaHora(props.fecha, props.ahora))
</script>

<template>
  <span v-if="!pendiente" class="vencimiento vencimiento--sin-plazo" :title="titulo">—</span>
  <span v-else-if="vencido" class="vencimiento vencimiento--vencido" :title="titulo">
    <v-icon :icon="iconos.vencido" size="15" aria-hidden="true" />Venció {{ relativo.texto }}
  </span>
  <span v-else class="vencimiento num" :class="{ 'vencimiento--urgente': urgente }" :title="titulo">
    <v-icon :icon="iconos.plazo" size="15" aria-hidden="true" />{{ relativo.texto }}
  </span>
</template>

<style scoped>
.vencimiento {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  color: var(--suave);
  white-space: nowrap;
}

.vencimiento--sin-plazo {
  color: var(--tenue);
}

.vencimiento--vencido {
  color: var(--error);
  font-weight: 600;
}

.vencimiento--urgente {
  color: var(--advertencia);
  font-weight: 600;
}
</style>
