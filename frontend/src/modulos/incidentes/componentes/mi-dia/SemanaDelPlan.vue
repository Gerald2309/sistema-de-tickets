<script setup>
import { aFecha, colorDeNota, esFinDeSemana, empezarArrastre, hora, leerArrastre } from '../../mi-dia.js'
import { presentarPrioridad } from '../../presentacion.js'

/**
 * La semana, de lunes a domingo, en columnas, con tarjetas mínimas (franja del color de la prioridad). Se arrastra de un
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

const nombreDelDia = new Intl.DateTimeFormat('es', { weekday: 'short' })

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
      :class="{ 'semana__dia--hoy': dia === hoy, 'semana__dia--fin': esFinDeSemana(dia) }"
      @dragover.prevent
      @drop.prevent="alSoltar($event, dia)"
    >
      <button type="button" class="semana__cabecera" @click="emit('abrir-dia', dia)">
        <span class="semana__nombre">{{ nombreDelDia.format(aFecha(dia)) }}</span>
        <b class="semana__numero num">{{ Number(dia.slice(8)) }}</b>
        <span v-if="delDia(dia).length" class="semana__cuenta num">{{ delDia(dia).length }}</span>
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
  grid-template-columns: repeat(7, minmax(0, 1fr));
}

.semana__dia {
  min-height: 320px;
  border-left: 1px solid var(--borde);
}

/* El fin de semana se puede planear, pero se distingue de los días hábiles. */
.semana__dia--fin {
  background: var(--sutil);
}

.semana__dia:first-child {
  border-left: 0;
}

.semana__cabecera {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  width: 100%;
  padding: 8px 10px;
  text-align: left;
  background: transparent;
  border: 0;
  border-bottom: 1px solid var(--borde);
}

.semana__nombre {
  grid-column: 1;
  color: var(--suave);
  font-size: 12px;
  text-transform: capitalize;
}

.semana__numero {
  grid-column: 1;
  font-size: 18px;
  line-height: 1.2;
}

.semana__cuenta {
  grid-row: 1 / span 2;
  grid-column: 2;
  min-width: 20px;
  padding: 0 6px;
  color: var(--suave);
  font-size: 12px;
  text-align: center;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: 10px;
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
  margin: 6px;
  padding: 6px;
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
  font-size: 11.5px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
