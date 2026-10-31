<script setup>
import { aFecha } from '../../mi-dia.js'
import { presentarPrioridad } from '../../presentacion.js'

/** El mes en cuadrícula: cuántos hay planeados cada día, con puntos de prioridad. Pulsar un día abre su vista. */
const props = defineProps({
  casillas: { type: /** @type {import('vue').PropType<string[]>} */ (Array), required: true },
  mes: { type: String, required: true },
  entradas: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').EntradaDelPlan[]>} */ (Array),
    required: true,
  },
  hoy: { type: String, required: true },
})
const emit = defineEmits(['abrir-dia'])

const NOMBRES = ['lun', 'mar', 'mié', 'jue', 'vie', 'sáb', 'dom']
const MAXIMO_PUNTOS = 5

/** @param {string} dia */
const delDia = (dia) => props.entradas.filter((entrada) => entrada.fecha === dia)
/** @param {string} dia */
const etiqueta = (dia) => {
  const cantidad = delDia(dia).length
  return `${aFecha(dia).getDate()}${cantidad ? `, ${cantidad} planeado${cantidad > 1 ? 's' : ''}` : ''}`
}
</script>

<template>
  <div class="mes">
    <div v-for="nombre in NOMBRES" :key="nombre" class="mes__nombre" aria-hidden="true">{{ nombre }}</div>
    <button
      v-for="dia in casillas"
      :key="dia"
      type="button"
      class="mes__celda"
      :class="{ 'mes__celda--fuera': !dia.startsWith(mes.slice(0, 7)), 'mes__celda--hoy': dia === hoy }"
      :aria-label="etiqueta(dia)"
      @click="emit('abrir-dia', dia)"
    >
      <span class="mes__numero num">{{ aFecha(dia).getDate() }}</span>
      <span v-if="delDia(dia).length" class="mes__plan">
        <span class="mes__puntos" aria-hidden="true">
          <span
            v-for="entrada in delDia(dia).slice(0, MAXIMO_PUNTOS)"
            :key="entrada.id"
            class="mes__punto"
            :style="{
              background: entrada.incidente
                ? presentarPrioridad(entrada.incidente.prioridad).color
                : 'var(--borde-fuerte)',
            }"
          />
        </span>
        <span class="num">{{ delDia(dia).length }}</span>
      </span>
    </button>
  </div>
</template>

<style scoped>
.mes {
  display: grid;
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.mes__nombre {
  padding: 8px 10px;
  color: var(--suave);
  font-size: 12px;
  font-weight: 600;
  border-bottom: 1px solid var(--borde);
}

.mes__celda {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  min-height: 84px;
  padding: 8px 10px;
  text-align: left;
  border-right: 1px solid var(--borde);
  border-bottom: 1px solid var(--borde);
}

.mes__celda:nth-child(7n) {
  border-right: 0;
}

.mes__celda:hover,
.mes__celda:focus-visible {
  background: var(--sutil);
  outline: none;
}

.mes__celda--fuera {
  color: var(--borde-fuerte);
  background: var(--sutil);
}

.mes__numero {
  font-size: 12.5px;
  font-weight: 600;
}

.mes__celda--hoy .mes__numero {
  display: inline-grid;
  place-items: center;
  width: 22px;
  height: 22px;
  color: var(--superficie);
  background: var(--acento);
  border-radius: 50%;
}

.mes__plan {
  display: flex;
  gap: 6px;
  align-items: center;
  margin-top: 8px;
  color: var(--suave);
  font-size: 12px;
}

.mes__puntos {
  display: flex;
  gap: 3px;
}

.mes__punto {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
</style>
