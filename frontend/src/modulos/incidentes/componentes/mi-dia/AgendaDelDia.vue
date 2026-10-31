<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { JORNADA, aTramo, hora, leerArrastre } from '../../mi-dia.js'
import BloqueAgenda from './BloqueAgenda.vue'

/** @typedef {import('../../api/mi-dia.js').EntradaDelPlan} EntradaDelPlan */
/** @typedef {{ tipo: 'incidente' | 'nota', id: number, codigo?: string, texto: string }} OpcionRapida */

/**
 * El día por horas, de media en media hora. Se suelta algo a una hora (la guía marca dónde cae), se pulsa un
 * hueco libre para añadir ahí mismo y la línea roja marca la hora actual. Muestra la jornada habitual y deja
 * desplegar las horas tempranas.
 */
const props = defineProps({
  entradas: { type: /** @type {import('vue').PropType<EntradaDelPlan[]>} */ (Array), required: true },
  esHoy: { type: Boolean, default: false },
  minutoActual: { type: Number, required: true },
  ahoraId: { type: Number, default: undefined },
  // Lo que se puede añadir en un hueco: lo por planear y las notas sueltas.
  opciones: { type: /** @type {import('vue').PropType<OpcionRapida[]>} */ (Array), default: () => [] },
})
const emit = defineEmits([
  'soltar',
  'duracion',
  'quitar',
  'bloquear',
  'desbloquear',
  'marcar',
  'manana',
  'elegir',
  'crear-nota',
])

const ALTO_HORA = 52
const PX_POR_MINUTO = ALTO_HORA / 60
const MAXIMO_OPCIONES = 6

const ventana = ref(/** @type {HTMLElement | null} */ (null))
const agenda = ref(/** @type {HTMLElement | null} */ (null))
const mostrarTemprano = ref(false)
const inicioVisible = computed(() =>
  Math.min(
    mostrarTemprano.value ? JORNADA.temprano : JORNADA.inicio,
    ...props.entradas.map((entrada) => Math.floor(entrada.inicio / 60) * 60),
  ),
)
const horas = computed(() => {
  const lista = []
  for (let minuto = inicioVisible.value; minuto < JORNADA.fin; minuto += 60) {
    lista.push(minuto)
  }
  return lista
})
const alto = computed(() => (JORNADA.fin - inicioVisible.value) * PX_POR_MINUTO)
const lineaDeAhora = computed(() =>
  props.esHoy && props.minutoActual >= inicioVisible.value && props.minutoActual <= JORNADA.fin
    ? (props.minutoActual - inicioVisible.value) * PX_POR_MINUTO
    : null,
)

/**
 * @param {EntradaDelPlan} entrada
 * @returns {number} dónde empieza lo siguiente: hasta ahí se puede estirar
 */
function limiteDe(entrada) {
  const siguiente = props.entradas.find((otra) => otra.inicio >= entrada.inicio + entrada.duracion && otra !== entrada)
  return siguiente ? siguiente.inicio : JORNADA.fin
}

/**
 * @param {MouseEvent} evento
 * @returns {number} el tramo de media hora bajo el puntero
 */
function minutoEn(evento) {
  const y = evento.clientY - (agenda.value?.getBoundingClientRect().top ?? 0)
  const minuto = inicioVisible.value + aTramo(y / PX_POR_MINUTO)
  return Math.min(Math.max(minuto, inicioVisible.value), JORNADA.fin - JORNADA.paso)
}

/** @param {number} minuto */
const ocupado = (minuto) =>
  props.entradas.some((entrada) => minuto < entrada.inicio + entrada.duracion && entrada.inicio < minuto + JORNADA.paso)

const guia = ref(/** @type {{ minuto: number, texto: string } | null} */ (null))

/** @param {DragEvent} evento */
function alArrastrarEncima(evento) {
  const minuto = minutoEn(evento)
  guia.value = { minuto, texto: `Soltar a las ${hora(minuto)}` }
}

/** @param {DragEvent} evento */
function alSoltar(evento) {
  guia.value = null
  const arrastrado = leerArrastre(evento)
  if (arrastrado) {
    emit('soltar', arrastrado, minutoEn(evento))
  }
}

/** @param {MouseEvent} evento */
function alMoverPuntero(evento) {
  if (evento.buttons || /** @type {HTMLElement} */ (evento.target).closest('.bloque')) {
    guia.value = null
    return
  }
  const minuto = minutoEn(evento)
  guia.value = ocupado(minuto) ? null : { minuto, texto: `+ Añadir a las ${hora(minuto)}` }
}

/** @param {DragEvent} evento */
function alSalirArrastrando(evento) {
  if (!agenda.value?.contains(/** @type {Node | null} */ (evento.relatedTarget))) {
    guia.value = null
  }
}

// Añadir en un hueco: una ventanita junto al puntero con buscador de incidentes y notas, o una nota nueva.
const rapido = ref({ abierto: false, minuto: 0, x: 0, y: 0, texto: '' })
const resultados = computed(() => {
  const texto = rapido.value.texto.trim().toLowerCase()
  return props.opciones
    .filter((opcion) => !texto || `${opcion.codigo ?? ''} ${opcion.texto}`.toLowerCase().includes(texto))
    .slice(0, MAXIMO_OPCIONES)
})

/** @param {MouseEvent} evento */
function alPulsarAgenda(evento) {
  if (/** @type {HTMLElement} */ (evento.target).closest('.bloque')) {
    return
  }
  const minuto = minutoEn(evento)
  if (!ocupado(minuto)) {
    rapido.value = { abierto: true, minuto, x: evento.clientX, y: evento.clientY, texto: '' }
  }
}

/** @param {OpcionRapida} opcion */
function elegir(opcion) {
  rapido.value.abierto = false
  emit('elegir', { tipo: opcion.tipo, id: opcion.id }, rapido.value.minuto)
}

function crearDesdeRapido() {
  rapido.value.abierto = false
  emit('crear-nota', rapido.value.texto.trim(), rapido.value.minuto)
}

// Enter elige lo primero que coincide; si nada coincide, el texto queda como nota.
function confirmarRapido() {
  const [primero] = resultados.value
  if (primero) {
    elegir(primero)
  } else if (rapido.value.texto.trim()) {
    crearDesdeRapido()
  }
}

const campoRapido = ref(/** @type {HTMLInputElement | null} */ (null))
watch(
  () => rapido.value.abierto,
  async (abierto) => {
    if (abierto) {
      await nextTick()
      campoRapido.value?.focus()
    }
  },
)

// Al abrir, la agenda de hoy se ubica en la hora actual.
onMounted(async () => {
  await nextTick()
  if (props.esHoy && ventana.value) {
    ventana.value.scrollTop = Math.max(0, (props.minutoActual - inicioVisible.value - 60) * PX_POR_MINUTO)
  }
})
</script>

<template>
  <div ref="ventana" class="agenda-ventana">
    <button type="button" class="agenda__temprano" @click="mostrarTemprano = !mostrarTemprano">
      {{
        mostrarTemprano ? `Ocultar antes de las ${hora(JORNADA.inicio)}` : `Mostrar desde las ${hora(JORNADA.temprano)}`
      }}
    </button>
    <div
      ref="agenda"
      class="agenda"
      :style="{ height: `${alto}px` }"
      @dragover.prevent="alArrastrarEncima"
      @dragleave="alSalirArrastrando"
      @drop.prevent="alSoltar"
      @mousemove="alMoverPuntero"
      @mouseleave="guia = null"
      @click="alPulsarAgenda"
    >
      <div
        v-for="minuto in horas"
        :key="minuto"
        class="agenda__hora"
        :style="{ top: `${(minuto - inicioVisible) * PX_POR_MINUTO}px`, height: `${ALTO_HORA}px` }"
      >
        <span class="num">{{ hora(minuto) }}</span>
      </div>
      <div v-if="lineaDeAhora !== null" class="agenda__ahora" :style="{ top: `${lineaDeAhora}px` }" aria-hidden="true">
        <span class="num">{{ hora(minutoActual) }}</span>
      </div>
      <BloqueAgenda
        v-for="entrada in entradas"
        :key="entrada.id"
        :entrada="entrada"
        :es-ahora="entrada.id === ahoraId"
        :inicio-visible="inicioVisible"
        :limite="limiteDe(entrada)"
        :px-por-minuto="PX_POR_MINUTO"
        @duracion="emit('duracion', entrada, $event)"
        @quitar="emit('quitar', entrada)"
        @bloquear="emit('bloquear', entrada)"
        @desbloquear="emit('desbloquear', entrada)"
        @manana="emit('manana', entrada)"
        @marcar="entrada.nota && emit('marcar', entrada.nota, $event)"
      />
      <div
        v-if="guia"
        class="agenda__guia"
        :style="{ top: `${(guia.minuto - inicioVisible) * PX_POR_MINUTO}px` }"
        aria-hidden="true"
      >
        {{ guia.texto }}
      </div>
    </div>

    <v-menu
      v-model="rapido.abierto"
      :target="[rapido.x, rapido.y]"
      location="bottom start"
      :close-on-content-click="false"
    >
      <form class="rapido" @submit.prevent="confirmarRapido">
        <b class="rapido__titulo">¿Qué hará a las {{ hora(rapido.minuto) }}?</b>
        <input
          ref="campoRapido"
          v-model="rapido.texto"
          class="rapido__campo"
          maxlength="200"
          placeholder="Código, título o una nota nueva"
          aria-label="Qué hará"
        />
        <button
          v-for="opcion in resultados"
          :key="`${opcion.tipo}-${opcion.id}`"
          type="button"
          class="rapido__opcion"
          @click="elegir(opcion)"
        >
          <span v-if="opcion.codigo" class="rapido__codigo">{{ opcion.codigo }}</span>
          <span v-else class="nota">Nota</span>
          <span class="rapido__texto">{{ opcion.texto }}</span>
        </button>
        <button v-if="rapido.texto.trim()" type="button" class="rapido__opcion rapido__crear" @click="crearDesdeRapido">
          <span class="rapido__texto">Crear nota «{{ rapido.texto.trim() }}»</span
          ><kbd v-if="!resultados.length">Enter</kbd>
        </button>
      </form>
    </v-menu>
  </div>
</template>

<style scoped>
.agenda-ventana {
  max-height: 560px;
  overflow-y: auto;
}

.agenda__temprano {
  display: block;
  width: 100%;
  height: 30px;
  color: var(--acento-texto);
  font-size: 12.5px;
  font-weight: 600;
  border-bottom: 1px dashed var(--borde);
}

.agenda__temprano:hover,
.agenda__temprano:focus-visible {
  background: var(--acento-fondo);
  outline: none;
}

.agenda {
  position: relative;
  margin: 12px 12px 14px 0;
  cursor: copy;
}

.agenda__hora {
  position: absolute;
  right: 0;
  left: 0;
  border-top: 1px solid var(--borde);
}

.agenda__hora::after {
  position: absolute;
  top: 50%;
  right: 0;
  left: 56px;
  border-top: 1px dashed var(--resalte);
  content: '';
}

.agenda__hora span {
  position: absolute;
  top: -9px;
  left: 0;
  width: 48px;
  padding-right: 6px;
  color: var(--tenue);
  font-size: 11.5px;
  text-align: right;
  background: var(--superficie);
}

.agenda__ahora {
  position: absolute;
  right: 0;
  left: 56px;
  z-index: 3;
  border-top: 2px solid var(--error);
  pointer-events: none;
}

.agenda__ahora::before {
  position: absolute;
  top: -5px;
  left: -4px;
  width: 8px;
  height: 8px;
  background: var(--error);
  border-radius: 50%;
  content: '';
}

.agenda__ahora span {
  position: absolute;
  top: -10px;
  left: -56px;
  width: 48px;
  padding-right: 6px;
  color: var(--error);
  font-size: 11.5px;
  font-weight: 600;
  text-align: right;
  background: var(--superficie);
}

.agenda__guia {
  position: absolute;
  right: 0;
  left: 60px;
  z-index: 2;
  height: 25px;
  padding: 3px 8px;
  color: var(--acento-texto);
  font-size: 11.5px;
  font-weight: 600;
  background: var(--acento-fondo);
  border: 1px dashed var(--acento);
  border-radius: var(--radio);
  pointer-events: none;
}

.rapido {
  width: 320px;
  padding: 12px 12px 6px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  box-shadow: var(--sombra-2);
}

.rapido__titulo {
  display: block;
  font-size: 13.5px;
}

.rapido__campo {
  width: 100%;
  height: 34px;
  margin: 8px 0;
  padding: 0 10px;
  color: var(--texto);
  font: inherit;
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--radio);
  outline: none;
}

.rapido__campo:focus {
  border-color: var(--acento);
  box-shadow: var(--anillo-foco);
}

.rapido__opcion {
  display: flex;
  gap: 10px;
  align-items: center;
  width: calc(100% + 24px);
  min-height: 40px;
  margin: 0 -12px;
  padding: 6px 12px;
  text-align: left;
  border-top: 1px solid var(--borde);
}

.rapido__opcion:hover,
.rapido__opcion:focus-visible {
  background: var(--resalte);
  outline: none;
}

.rapido__codigo {
  color: var(--acento-texto);
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
}

.rapido__texto {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.rapido__crear {
  color: var(--acento-texto);
  font-weight: 600;
}

kbd {
  padding: 0 5px;
  color: var(--suave);
  font: 600 11px/18px var(--fuente);
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: 3px;
}
</style>
