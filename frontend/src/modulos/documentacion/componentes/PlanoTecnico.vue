<script setup>
import { computed, ref, useId } from 'vue'
import { idDeArista, trazar } from '../plano.js'

/**
 * Un plano como dibujo técnico: papel cuadriculado, marco, zonas punteadas, cajas, flechas en ángulo recto y
 * cajetín. Si se indica `iluminados`, esas cajas y flechas se resaltan y el resto se atenúa. Las cajas con
 * `pagina` son enlaces: al pasar por encima se iluminan con sus conexiones y al elegirlas emiten `elegir`.
 */
const props = defineProps({
  plano: { type: /** @type {import('vue').PropType<import('../tipos.js').Plano>} */ (Object), required: true },
  iluminados: {
    type: /** @type {import('vue').PropType<{ nodos: string[], aristas: string[] } | null>} */ (Object),
    default: null,
  },
})
const emit = defineEmits(['elegir'])

// La caja sobre la que está el puntero o el foco, en un plano navegable.
const encima = ref(/** @type {string | null} */ (null))
const resaltados = computed(() => {
  if (!encima.value) {
    return props.iluminados
  }
  const conectadas = (props.plano.aristas ?? []).filter((a) => a.de === encima.value || a.a === encima.value)
  return {
    nodos: [encima.value, ...conectadas.flatMap((a) => [a.de, a.a])],
    aristas: conectadas.map(idDeArista),
  }
})

// Las definiciones del SVG (cuadrícula, puntas de flecha) llevan un id propio: una página tiene varios planos.
const id = useId()
const CAJETIN = { ancho: 250, alto: 58, margen: 16 }

const zonas = computed(() => props.plano.nodos.filter((nodo) => nodo.zona))
const cajas = computed(() => props.plano.nodos.filter((nodo) => !nodo.zona))
const navegable = computed(() => cajas.value.some((caja) => caja.pagina))
const flechas = computed(() => {
  const porId = Object.fromEntries(props.plano.nodos.map((nodo) => [nodo.id, nodo]))
  return (props.plano.aristas ?? []).map((arista) => ({
    id: idDeArista(arista),
    arista,
    trazo: trazar(porId[arista.de], porId[arista.a], arista),
  }))
})
const cajetin = computed(() => ({
  x: props.plano.ancho - CAJETIN.ancho - CAJETIN.margen,
  y: props.plano.alto - CAJETIN.alto - CAJETIN.margen,
}))

/** @param {string} nodo */
const estadoDe = (nodo) =>
  !resaltados.value ? '' : resaltados.value.nodos.includes(nodo) ? 'plano__caja--activa' : 'plano__caja--atenuada'
/** @param {string} arista */
const activa = (arista) => resaltados.value?.aristas.includes(arista) ?? false
</script>

<template>
  <div class="plano">
    <svg
      :viewBox="`0 0 ${plano.ancho} ${plano.alto}`"
      :role="navegable ? 'group' : 'img'"
      :aria-label="`Plano ${plano.codigo}: ${plano.titulo}`"
      class="plano__lienzo"
    >
      <defs>
        <pattern :id="`${id}-fina`" width="10" height="10" patternUnits="userSpaceOnUse">
          <path d="M10 0H0V10" class="plano__reticula-fina" />
        </pattern>
        <pattern :id="`${id}-gruesa`" width="50" height="50" patternUnits="userSpaceOnUse">
          <rect width="50" height="50" :fill="`url(#${id}-fina)`" />
          <path d="M50 0H0V50" class="plano__reticula-gruesa" />
        </pattern>
        <marker
          v-for="tipo in ['', '-activa']"
          :id="`${id}-punta${tipo}`"
          :key="tipo"
          viewBox="0 0 10 10"
          refX="9"
          refY="5"
          markerWidth="7"
          markerHeight="7"
          orient="auto-start-reverse"
        >
          <path d="M0 0L10 5L0 10z" :class="tipo ? 'plano__punta--activa' : 'plano__punta'" />
        </marker>
      </defs>

      <rect :width="plano.ancho" :height="plano.alto" :fill="`url(#${id}-gruesa)`" />
      <rect x="8" y="8" :width="plano.ancho - 16" :height="plano.alto - 16" class="plano__marco" />

      <g v-for="zona in zonas" :key="zona.id">
        <rect :x="zona.x" :y="zona.y" :width="zona.w" :height="zona.h" rx="2" class="plano__zona" />
        <text :x="zona.x + 12" :y="zona.y + 18" class="plano__zona-titulo">{{ zona.titulo }}</text>
      </g>

      <g
        v-for="flecha in flechas"
        :key="flecha.id"
        class="plano__flecha"
        :class="{ 'plano__flecha--punteada': flecha.arista.punteada, 'plano__flecha--activa': activa(flecha.id) }"
      >
        <path :d="flecha.trazo.d" :marker-end="`url(#${id}-punta${activa(flecha.id) ? '-activa' : ''})`" />
        <text v-if="flecha.arista.texto" :x="flecha.trazo.x" :y="flecha.trazo.y" :text-anchor="flecha.trazo.ancla">
          {{ flecha.arista.texto }}
        </text>
      </g>

      <g
        v-for="caja in cajas"
        :key="caja.id"
        class="plano__caja"
        :class="[{ 'plano__caja--clave': caja.clave, 'plano__caja--enlace': caja.pagina }, estadoDe(caja.id)]"
        :role="caja.pagina ? 'link' : undefined"
        :tabindex="caja.pagina ? 0 : undefined"
        :aria-label="caja.pagina ? `${caja.titulo}: ${caja.sub ?? ''}` : undefined"
        @click="caja.pagina && emit('elegir', caja.pagina)"
        @keydown.enter="caja.pagina && emit('elegir', caja.pagina)"
        @mouseenter="caja.pagina && (encima = caja.id)"
        @mouseleave="encima = null"
        @focus="caja.pagina && (encima = caja.id)"
        @blur="encima = null"
      >
        <rect :x="caja.x" :y="caja.y" :width="caja.w" :height="caja.h" rx="3" />
        <text v-if="caja.tipo" :x="caja.x + 10" :y="caja.y + 15" class="plano__tipo">{{ caja.tipo }}</text>
        <text :x="caja.x + 10" :y="caja.y + (caja.tipo ? 32 : 22)" class="plano__titulo">{{ caja.titulo }}</text>
        <text v-if="caja.sub" :x="caja.x + 10" :y="caja.y + (caja.tipo ? 48 : 38)" class="plano__sub">
          {{ caja.sub }}
        </text>
      </g>

      <!-- Cajetín, como en un plano de arquitectura. -->
      <g class="plano__cajetin" :transform="`translate(${cajetin.x} ${cajetin.y})`">
        <rect width="250" height="58" />
        <line x1="0" y1="29" x2="250" y2="29" />
        <line x1="70" y1="0" x2="70" y2="58" />
        <line x1="170" y1="29" x2="170" y2="58" />
        <text x="6" y="11" class="plano__rotulo">PLANO</text>
        <text x="6" y="24" class="plano__dato plano__dato--fuerte">{{ plano.codigo }}</text>
        <text x="76" y="11" class="plano__rotulo">TÍTULO</text>
        <text x="76" y="24" class="plano__dato">{{ plano.titulo }}</text>
        <text x="6" y="40" class="plano__rotulo">NIVEL</text>
        <text x="6" y="52" class="plano__dato">{{ plano.nivel }}</text>
        <text x="76" y="40" class="plano__rotulo">PROYECTO</text>
        <text x="76" y="52" class="plano__dato">Mesa de ayuda</text>
        <text x="176" y="40" class="plano__rotulo">ESCALA</text>
        <text x="176" y="52" class="plano__dato">S/E</text>
      </g>
    </svg>
  </div>
</template>

<style scoped>
.plano {
  overflow-x: auto;
  background: var(--superficie);
}

.plano__lienzo {
  display: block;
  width: 100%;
  min-width: 760px;
  height: auto;
}

.plano__reticula-fina {
  fill: none;
  stroke: var(--sutil);
}

.plano__reticula-gruesa {
  fill: none;
  stroke: var(--resalte);
}

.plano__marco {
  fill: none;
  stroke: var(--texto);
}

.plano__zona {
  fill: var(--info-fondo);
  stroke: var(--acento-borde);
  stroke-dasharray: 5 4;
}

.plano__zona-titulo {
  font: 600 10px var(--mono);
  letter-spacing: 0.12em;
  fill: var(--acento-texto);
}

.plano__caja rect {
  fill: var(--superficie);
  stroke: var(--borde-fuerte);
  transition:
    fill var(--rapida),
    stroke var(--rapida);
}

.plano__caja--clave rect {
  stroke: var(--acento);
  stroke-width: 1.5;
}

.plano__caja--activa rect {
  fill: var(--acento-fondo);
  stroke: var(--acento);
  stroke-width: 2;
}

.plano__caja--atenuada {
  opacity: 0.45;
}

.plano__caja--enlace {
  cursor: pointer;
  outline: none;
}

.plano__caja--enlace:focus-visible rect {
  stroke: var(--acento);
  stroke-width: 3;
}

.plano__tipo {
  font: 600 9px var(--mono);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  fill: var(--tenue);
}

.plano__titulo {
  font: 600 13px var(--fuente);
  fill: var(--texto);
}

.plano__sub {
  font: 11px var(--mono);
  fill: var(--suave);
}

.plano__flecha path {
  fill: none;
  stroke: var(--tenue);
  stroke-width: 1.2;
}

.plano__flecha--punteada path {
  stroke-dasharray: 4 4;
}

.plano__flecha text {
  font: 10.5px var(--mono);
  fill: var(--suave);
  paint-order: stroke;
  stroke: var(--superficie);
  stroke-width: 4px;
}

.plano__flecha--activa path {
  stroke: var(--acento);
  stroke-width: 2.2;
  stroke-dasharray: 6 5;
  animation: flujo 0.8s linear infinite;
}

.plano__punta {
  fill: var(--tenue);
}

.plano__punta--activa {
  fill: var(--acento);
}

.plano__cajetin rect {
  fill: var(--superficie);
  stroke: var(--texto);
}

.plano__cajetin line {
  stroke: var(--texto);
  stroke-width: 0.6;
}

.plano__rotulo {
  font: 8px var(--mono);
  letter-spacing: 0.1em;
  fill: var(--tenue);
}

.plano__dato {
  font: 9.5px var(--mono);
  fill: var(--texto);
}

.plano__dato--fuerte {
  font-weight: 600;
}

@keyframes flujo {
  to {
    stroke-dashoffset: -11;
  }
}

@media (prefers-reduced-motion: reduce) {
  .plano__flecha--activa path {
    animation: none;
  }
}
</style>
