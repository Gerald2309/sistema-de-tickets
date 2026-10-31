<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * Desde y hasta para filtrar por días (calendario de Vuetify en español, semana desde el lunes). Trabaja con
 * días AAAA-MM-DD locales, que es como viajan en la dirección.
 */
const desde = defineModel('desde', { type: String, default: undefined })
const hasta = defineModel('hasta', { type: String, default: undefined })

/**
 * @param {Date | null} fecha el calendario entrega un Date local
 * @returns {string | undefined}
 */
const aDia = (fecha) => (fecha ? fecha.toLocaleDateString('sv-SE') : undefined)
/**
 * @param {string | undefined} dia
 * @returns {Date | null}
 */
const aFecha = (dia) => (dia ? new Date(`${dia}T00:00:00`) : null)
</script>

<template>
  <div class="rango">
    <v-date-input
      :model-value="aFecha(desde)"
      label="Desde"
      :first-day-of-week="1"
      clearable
      hide-details
      prepend-icon=""
      :prepend-inner-icon="iconos.calendario"
      :max="aFecha(hasta) ?? undefined"
      @update:model-value="desde = aDia($event)"
    />
    <v-date-input
      :model-value="aFecha(hasta)"
      label="Hasta"
      :first-day-of-week="1"
      clearable
      hide-details
      prepend-icon=""
      :prepend-inner-icon="iconos.calendario"
      :min="aFecha(desde) ?? undefined"
      @update:model-value="hasta = aDia($event)"
    />
  </div>
</template>

<style scoped>
.rango {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
</style>
