<script setup>
import { computed } from 'vue'
import { DISPARADORES, PLAZOS, UNIDADES, aEspera, disparoInicial } from '../catalogo.js'

/** El paso «Cuándo»: el disparador y, si depende del tiempo, cuánto esperar o qué parte del plazo. */
const props = defineProps({
  disparo: { type: /** @type {import('vue').PropType<import('../tipos.js').Disparo>} */ (Object), required: true },
})
const emit = defineEmits({ cambiar: (/** @type {import('../tipos.js').Disparo} */ disparo) => !!disparo })

const disparadores = Object.entries(DISPARADORES).map(([valor, { nombre }]) => ({ valor, nombre }))
const plazos = Object.entries(PLAZOS).map(([valor, nombre]) => ({ valor, nombre: `Plazo de ${nombre}` }))
const medida = computed(() => DISPARADORES[props.disparo.disparador]?.medida)
const espera = computed(() => aEspera(props.disparo.espera_minutos ?? 60))
// La espera se elige en horas o días; los minutos solo aparecen si la API ya trae una espera así.
const unidades = computed(() =>
  Object.entries(UNIDADES)
    .filter(([clave]) => clave !== 'minutos' || espera.value.unidad === 'minutos')
    .map(([valor, { plural }]) => ({ valor, nombre: plural })),
)

/** @param {Partial<import('../tipos.js').Disparo>} cambios */
const cambiar = (cambios) => emit('cambiar', { ...props.disparo, ...cambios })

/**
 * @param {number} cantidad
 * @param {keyof typeof UNIDADES} unidad
 */
const cambiarEspera = (cantidad, unidad) =>
  cambiar({ espera_minutos: Math.max(1, Math.round(cantidad)) * UNIDADES[unidad].minutos })
</script>

<template>
  <div>
    <v-select
      label="Disparador"
      :items="disparadores"
      item-title="nombre"
      item-value="valor"
      :model-value="disparo.disparador"
      @update:model-value="emit('cambiar', disparoInicial($event))"
    />
    <div v-if="medida === 'espera'" class="fila">
      <v-text-field
        label="Esperar"
        type="number"
        min="1"
        class="fila__cantidad"
        :model-value="espera.cantidad"
        @update:model-value="cambiarEspera(Number($event), espera.unidad)"
      />
      <v-select
        label="Unidad"
        :items="unidades"
        item-title="nombre"
        item-value="valor"
        :model-value="espera.unidad"
        @update:model-value="cambiarEspera(espera.cantidad, $event)"
      />
    </div>
    <v-text-field
      v-if="medida === 'porcentaje'"
      label="Al llegar a este porcentaje del plazo"
      type="number"
      min="1"
      max="99"
      suffix="%"
      :model-value="disparo.porcentaje"
      @update:model-value="cambiar({ porcentaje: Number($event) })"
    />
    <v-select
      v-if="medida === 'porcentaje' || medida === 'plazo'"
      label="Plazo"
      :items="plazos"
      item-title="nombre"
      item-value="valor"
      :model-value="disparo.plazo"
      @update:model-value="cambiar({ plazo: $event })"
    />
  </div>
</template>

<style scoped>
.fila {
  display: flex;
  gap: 8px;
}

.fila__cantidad {
  flex: 0 0 110px;
}
</style>
