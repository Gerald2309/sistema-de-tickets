<script setup>
import { computed } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'

/**
 * Los incidentes que entran contra los que se resuelven, día a día. La frase dice si la cola crece o baja;
 * la gráfica solo muestra la forma.
 */
const props = defineProps({
  tendencia: {
    type: /** @type {import('vue').PropType<import('../tipos.js').IndicadoresPanel['tendencia'] | undefined>} */ (
      Array
    ),
    default: undefined,
  },
})

const ANCHO = 400
const ALTO = 100

const lineas = computed(() => {
  const dias = props.tendencia ?? []
  const mayor = Math.max(1, ...dias.flatMap((dia) => [dia.entraron, dia.resueltos]))
  /** @param {'entraron' | 'resueltos'} campo */
  const puntos = (campo) =>
    dias
      .map((dia, indice) => {
        const x = dias.length > 1 ? (indice / (dias.length - 1)) * ANCHO : 0
        const y = ALTO - 4 - (dia[campo] / mayor) * (ALTO - 8)
        return `${Math.round(x)},${Math.round(y)}`
      })
      .join(' ')
  return { entraron: puntos('entraron'), resueltos: puntos('resueltos') }
})

const totales = computed(() =>
  (props.tendencia ?? []).reduce(
    (suma, dia) => ({ entraron: suma.entraron + dia.entraron, resueltos: suma.resueltos + dia.resueltos }),
    { entraron: 0, resueltos: 0 },
  ),
)

const frase = computed(() => {
  const diferencia = totales.value.entraron - totales.value.resueltos
  if (diferencia > 0) {
    return `Entran más de los que se resuelven: la cola creció ${diferencia}.`
  }
  if (diferencia < 0) {
    return `Se resuelven más de los que entran: la cola bajó ${-diferencia}.`
  }
  return 'Se resuelven tantos como entran: la cola se mantiene.'
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-tendencia">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-tendencia" class="tarjeta__titulo">Cómo vamos</h2>
      <span class="nota">últimos {{ tendencia?.length ?? 14 }} días</span>
    </header>
    <div class="tendencia">
      <template v-if="tendencia">
        <p class="tendencia__frase">{{ frase }}</p>
        <svg
          class="tendencia__grafica"
          :viewBox="`0 0 ${ANCHO} ${ALTO}`"
          preserveAspectRatio="none"
          role="img"
          :aria-label="`Entraron ${totales.entraron} y se resolvieron ${totales.resueltos} en ${tendencia.length} días`"
        >
          <polyline class="tendencia__linea tendencia__linea--entran" :points="lineas.entraron" />
          <polyline class="tendencia__linea tendencia__linea--resueltos" :points="lineas.resueltos" />
        </svg>
        <p class="tendencia__leyenda">
          <span><i class="tendencia__muestra tendencia__muestra--entran" />entraron {{ totales.entraron }}</span>
          <span
            ><i class="tendencia__muestra tendencia__muestra--resueltos" />se resolvieron {{ totales.resueltos }}</span
          >
        </p>
      </template>
      <EsqueletoDeCarga v-else alto="130px" />
    </div>
  </section>
</template>

<style scoped>
.tendencia {
  padding: 12px 16px 14px;
}

.tendencia__frase {
  margin: 0 0 10px;
  color: var(--suave);
  font-size: 13px;
}

.tendencia__grafica {
  display: block;
  width: 100%;
  height: 90px;
  overflow: visible;
}

.tendencia__linea {
  fill: none;
  stroke-width: 2;
  stroke-linejoin: round;
  vector-effect: non-scaling-stroke;
}

.tendencia__linea--entran {
  stroke: var(--grafico-resalte);
}

.tendencia__linea--resueltos {
  stroke: var(--estado-resuelto);
}

.tendencia__leyenda {
  display: flex;
  gap: 16px;
  margin: 8px 0 0;
  color: var(--suave);
  font-size: 12px;
}

.tendencia__muestra {
  display: inline-block;
  width: 8px;
  height: 8px;
  margin-right: 6px;
  border-radius: 50%;
}

.tendencia__muestra--entran {
  background: var(--grafico-resalte);
}

.tendencia__muestra--resueltos {
  background: var(--estado-resuelto);
}
</style>
