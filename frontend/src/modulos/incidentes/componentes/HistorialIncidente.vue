<script setup>
import { computed } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { useBuscadorDePersonas } from '@/modulos/identidad/index.js'
import { describirEntrada, personasDelHistorial } from '../historial.js'

/**
 * Línea de tiempo del incidente, lo más reciente arriba (RF-F30). Llega por tramos: «Ver más» trae los cambios
 * anteriores, así un incidente muy trabajado no alarga la página sin fin. Una entrada nueva entra deslizándose.
 */
const props = defineProps({
  entradas: {
    type: /** @type {import('vue').PropType<import('../tipos.js').EntradaHistorial[] | undefined>} */ (Array),
    default: undefined,
  },
  cargando: { type: Boolean, default: false },
  hayMas: { type: Boolean, default: false },
  cargandoMas: { type: Boolean, default: false },
})
const emit = defineEmits(['ver-mas'])

const asignadas = computed(() => personasDelHistorial(props.entradas ?? []))
const personas = useBuscadorDePersonas({ incluirInactivas: true, elegidos: asignadas })

const lineas = computed(() =>
  [...(props.entradas ?? [])]
    .sort((a, b) => b.creadoEn.localeCompare(a.creadoEn) || b.id - a.id)
    .map((entrada) => ({
      ...describirEntrada(entrada, personas.nombreDe),
      id: entrada.id,
      autor: entrada.origen
        ? `${entrada.autor.nombre}, por la automatización «${entrada.origen}»`
        : entrada.autor.nombre,
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
          <details v-if="linea.comparacion" class="comparacion">
            <summary class="comparacion__resumen">Ver antes y después</summary>
            <dl class="comparacion__textos">
              <dt>Antes</dt>
              <dd>{{ linea.comparacion.antes ?? '—' }}</dd>
              <dt>Después</dt>
              <dd>{{ linea.comparacion.despues ?? '—' }}</dd>
            </dl>
          </details>
          <div class="nota">{{ linea.autor }} · {{ linea.cuando }}</div>
        </li>
      </TransitionGroup>
    </div>
    <footer v-if="hayMas" class="historial__pie">
      <v-btn variant="text" size="small" :loading="cargandoMas" :append-icon="iconos.desplegar" @click="emit('ver-mas')"
        >Ver cambios anteriores</v-btn
      >
    </footer>
  </section>
</template>

<style scoped>
.historial__pie {
  display: flex;
  justify-content: center;
  padding: 4px 16px 10px;
  border-top: 1px solid var(--borde);
}

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

.comparacion {
  margin: 4px 0;
}

.comparacion__resumen {
  width: fit-content;
  color: var(--acento);
  font-size: 13px;
  cursor: pointer;
}

.comparacion__textos {
  margin: 6px 0 0;
  padding: 8px 12px;
  font-size: 13px;
  background: var(--sutil);
  border-radius: var(--radio);
}

.comparacion__textos dt {
  color: var(--suave);
  font-weight: 500;
}

.comparacion__textos dd {
  margin: 0 0 8px;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.comparacion__textos dd:last-child {
  margin-bottom: 0;
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
