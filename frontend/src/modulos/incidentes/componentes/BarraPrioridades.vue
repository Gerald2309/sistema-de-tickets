<script setup>
import { computed } from 'vue'
import ContadorAnimado from '@/compartido/componentes/ContadorAnimado.vue'
import { PRIORIDADES } from '../presentacion.js'

/** Incidentes por prioridad: barra apilada y leyenda, de crítica a baja (RF-F17). */
const props = defineProps({
  porPrioridad: { type: /** @type {import('vue').PropType<Record<string, number>>} */ (Object), required: true },
  total: { type: Number, required: true },
})

const filas = computed(() =>
  Object.entries(PRIORIDADES).map(([valor, presentacion]) => {
    const cantidad = props.porPrioridad[valor] ?? 0
    return {
      valor,
      ...presentacion,
      cantidad,
      porcentaje: props.total ? Math.round((cantidad / props.total) * 100) : 0,
    }
  }),
)
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-prioridades">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-prioridades" class="tarjeta__titulo">Por prioridad</h2>
      <span class="nota num">{{ total }}</span>
    </header>
    <div class="tarjeta__cuerpo">
      <div class="apilada" aria-hidden="true">
        <span
          v-for="(fila, indice) in filas"
          :key="fila.valor"
          class="apilada__segmento"
          :style="{ flex: fila.cantidad, background: fila.color, '--i': indice }"
        />
      </div>
      <ul class="leyenda">
        <li v-for="fila in filas" :key="fila.valor" class="leyenda__fila">
          <v-icon :icon="fila.icono" size="16" :style="{ color: fila.color }" class="mr-2" />{{ fila.nombre }}
          <b class="leyenda__cantidad"><ContadorAnimado :valor="fila.cantidad" /></b>
          <span class="leyenda__porcentaje num">{{ fila.porcentaje }} %</span>
        </li>
      </ul>
    </div>
  </section>
</template>

<style scoped>
.apilada {
  display: flex;
  gap: 2px;
  height: 10px;
  overflow: hidden;
  border-radius: 3px;
}

.apilada__segmento {
  transform-origin: left;
  animation: crece 0.9s var(--curva) both;
  animation-delay: calc(var(--i, 0) * 90ms + 0.2s);
}

.leyenda {
  display: flex;
  flex-direction: column;
  gap: 11px;
  margin: 16px 0 0;
  padding: 0;
  list-style: none;
}

.leyenda__fila {
  display: flex;
  align-items: center;
  font-size: 13.5px;
}

.leyenda__cantidad {
  margin-left: auto;
  font-weight: 600;
}

.leyenda__porcentaje {
  width: 46px;
  color: var(--tenue);
  text-align: right;
}
</style>
