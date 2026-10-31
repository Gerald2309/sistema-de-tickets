<script setup>
import { computed } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { useBuscadorDePersonas } from '@/modulos/identidad/index.js'
import { describirEntrada, personasDelHistorial } from '../historial.js'

/** Línea de tiempo del incidente, lo más reciente arriba (RF-F30). Una entrada nueva entra deslizándose. */
const props = defineProps({
  entradas: {
    type: /** @type {import('vue').PropType<import('../tipos.js').EntradaHistorial[] | undefined>} */ (Array),
    default: undefined,
  },
  cargando: { type: Boolean, default: false },
})

const asignadas = computed(() => personasDelHistorial(props.entradas ?? []))
const personas = useBuscadorDePersonas({ incluirInactivas: true, elegidos: asignadas })

const lineas = computed(() =>
  [...(props.entradas ?? [])]
    .sort((a, b) => b.creadoEn.localeCompare(a.creadoEn) || b.id - a.id)
    .map((entrada) => ({
      ...describirEntrada(entrada, personas.nombreDe),
      id: entrada.id,
      autor: entrada.autor.nombre,
      cuando: fechaHora(entrada.creadoEn),
    })),
)
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-historial">
    <header class="tarjeta__cabecera"><h2 id="titulo-historial" class="tarjeta__titulo">Historial</h2></header>
    <div class="tarjeta__cuerpo">
      <div v-if="cargando" class="historial__cargando">
        <EsqueletoDeCarga v-for="n in 3" :key="n" :ancho="`${220 - n * 30}px`" />
      </div>
      <TransitionGroup v-else name="historial" tag="ol" class="linea">
        <li v-for="linea in lineas" :key="linea.id" class="linea__entrada" :style="{ '--color': linea.color }">
          <b class="linea__texto">{{ linea.texto }}</b>
          <span v-if="linea.detalle" class="linea__detalle"> · {{ linea.detalle }}</span>
          <div class="nota">{{ linea.autor }} · {{ linea.cuando }}</div>
        </li>
      </TransitionGroup>
    </div>
  </section>
</template>

<style scoped>
.historial__cargando {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.linea {
  position: relative;
  margin: 0;
  padding: 0 0 0 22px;
  list-style: none;
}

.linea::before {
  position: absolute;
  top: 6px;
  bottom: 6px;
  left: 5px;
  width: 1px;
  background: var(--borde-fuerte);
  content: '';
}

.linea__entrada {
  position: relative;
  padding-bottom: 16px;
  font-size: 14px;
}

.linea__entrada:last-child {
  padding-bottom: 0;
}

.linea__entrada::before {
  position: absolute;
  top: 5px;
  left: -22px;
  width: 11px;
  height: 11px;
  background: var(--color);
  border: 2px solid var(--superficie);
  border-radius: 50%;
  box-shadow: 0 0 0 1px var(--borde-fuerte);
  content: '';
}

.linea__texto {
  font-weight: 500;
}

.linea__detalle {
  color: var(--suave);
}

.historial-enter-active {
  transition:
    opacity 0.6s var(--curva),
    transform 0.6s var(--curva);
}

.historial-enter-from {
  opacity: 0;
  transform: translateY(-12px);
}

.historial-move {
  transition: transform 0.5s var(--curva);
}
</style>
