<script setup>
import { aFecha, colorDeNota, empezarArrastre, hora, leerArrastre } from '../../mi-dia.js'
import { presentarPrioridad } from '../../presentacion.js'

/**
 * La semana laboral en columnas, con tarjetas mínimas (franja del color de la prioridad). Se arrastra de un
 * día a otro y pulsar el día abre su vista por horas.
 */
const props = defineProps({
  dias: { type: /** @type {import('vue').PropType<string[]>} */ (Array), required: true },
  entradas: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').EntradaDelPlan[]>} */ (Array),
    required: true,
  },
  hoy: { type: String, required: true },
})
const emit = defineEmits(['abrir-dia', 'soltar'])

const formato = new Intl.DateTimeFormat('es', { weekday: 'short', day: 'numeric' })

/** @param {string} dia */
const delDia = (dia) => props.entradas.filter((entrada) => entrada.fecha === dia)

/** @param {import('../../api/mi-dia.js').EntradaDelPlan} entrada */
const color = (entrada) =>
  entrada.nota
    ? `var(--papel-${colorDeNota(entrada.nota.id)}-borde)`
    : presentarPrioridad(entrada.incidente?.prioridad ?? 'baja').color

/**
 * @param {DragEvent} evento
 * @param {string} dia
 */
function alSoltar(evento, dia) {
  const arrastrado = leerArrastre(evento)
  if (arrastrado) {
    emit('soltar', arrastrado, dia)
  }
}
</script>

<template>
  <div class="semana">
    <div
      v-for="dia in dias"
      :key="dia"
      class="semana__dia"
      :class="{ 'semana__dia--hoy': dia === hoy }"
      @dragover.prevent
      @drop.prevent="alSoltar($event, dia)"
    >
      <button type="button" class="semana__cabecera" @click="emit('abrir-dia', dia)">
        <b>{{ formato.format(aFecha(dia)) }}</b>
        <span class="nota num">{{ delDia(dia).length }}</span>
      </button>
      <div
        v-for="entrada in delDia(dia)"
        :key="entrada.id"
        class="semana__mini"
        :style="{ '--color': color(entrada) }"
        draggable="true"
        @dragstart="empezarArrastre($event, { tipo: 'entrada', id: entrada.id })"
      >
        <span class="semana__hora num">{{ hora(entrada.inicio) }}</span>
        <span v-if="entrada.incidente" class="semana__codigo">{{ entrada.incidente.codigo }}</span>
        <span class="semana__texto">{{ entrada.incidente?.titulo ?? entrada.nota?.texto }}</span>
      </div>
      <p v-if="delDia(dia).length === 0" class="semana__libre">Libre</p>
    </div>
  </div>
</template>

<style scoped>
.semana {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
}

.semana__dia {
  min-height: 320px;
  border-left: 1px solid var(--borde);
}

.semana__dia:first-child {
  border-left: 0;
}

.semana__cabecera {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  width: 100%;
  padding: 10px 12px;
  text-transform: capitalize;
  border-bottom: 1px solid var(--borde);
}

.semana__cabecera:hover,
.semana__cabecera:focus-visible {
  background: var(--sutil);
  outline: none;
}

.semana__dia--hoy .semana__cabecera {
  color: var(--acento-texto);
  box-shadow: inset 0 2px 0 var(--acento);
}

.semana__mini {
  display: flex;
  flex-direction: column;
  margin: 8px;
  padding: 6px 8px;
  font-size: 12.5px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-left: 3px solid var(--color);
  border-radius: var(--radio);
  cursor: grab;
}

.semana__hora {
  color: var(--suave);
  font-size: 11.5px;
}

.semana__codigo {
  color: var(--acento-texto);
  font-size: 12px;
  font-weight: 600;
}

.semana__texto {
  display: -webkit-box;
  overflow: hidden;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.semana__libre {
  padding: 14px 8px;
  color: var(--tenue);
  font-size: 12.5px;
  text-align: center;
}

@media (width <= 900px) {
  .semana {
    grid-template-columns: 1fr;
  }

  .semana__dia {
    min-height: 0;
    border-top: 1px solid var(--borde);
    border-left: 0;
  }
}
</style>
