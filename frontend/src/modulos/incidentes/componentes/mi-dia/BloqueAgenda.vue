<script setup>
import { computed, ref } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import {
  JORNADA,
  colorDeNota,
  duracionLegible,
  empezarArrastre,
  estaTerminada,
  hora,
  siguienteDuracion,
} from '../../mi-dia.js'
import { presentarPrioridad } from '../../presentacion.js'
import CodigoTicket from '../CodigoTicket.vue'
import IndicadorDeVencimiento from '../IndicadorDeVencimiento.vue'

/**
 * Un bloque de la agenda. En reposo muestra código, título y horario; las acciones aparecen al pasar o con el
 * teclado. La duración se cambia pulsándola o estirando el borde de abajo, sin pasar de `limite`.
 */
const props = defineProps({
  entrada: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').EntradaDelPlan>} */ (Object),
    required: true,
  },
  esAhora: { type: Boolean, default: false },
  inicioVisible: { type: Number, required: true },
  // Minuto en el que empieza lo siguiente (o termina la jornada): hasta ahí se puede estirar.
  limite: { type: Number, required: true },
  pxPorMinuto: { type: Number, required: true },
})
const emit = defineEmits(['duracion', 'quitar', 'bloquear', 'desbloquear', 'marcar', 'manana'])

const terminada = computed(() => estaTerminada(props.entrada))
const incidente = computed(() => props.entrada.incidente)
const nota = computed(() => props.entrada.nota)
const estirando = ref(/** @type {number | null} */ (null))
const duracion = computed(() => estirando.value ?? props.entrada.duracion)
const color = computed(() =>
  nota.value
    ? `var(--papel-${colorDeNota(nota.value.id)}-borde)`
    : presentarPrioridad(incidente.value?.prioridad ?? 'baja').color,
)
const estilo = computed(() => ({
  top: `${(props.entrada.inicio - props.inicioVisible) * props.pxPorMinuto + 1}px`,
  height: `${duracion.value * props.pxPorMinuto - 3}px`,
  '--color': color.value,
  '--tinte': nota.value ? `var(--papel-${colorDeNota(nota.value.id)})` : undefined,
}))
const corto = computed(() => duracion.value < 60)
const etiquetaDuracion = computed(() => duracionLegible(props.entrada.duracion))

function cambiarDuracion() {
  const nueva = siguienteDuracion(props.entrada.duracion)
  emit('duracion', props.entrada.inicio + nueva <= props.limite ? nueva : JORNADA.duraciones[0])
}

/**
 * Estirar el borde inferior: la duración sigue al puntero de media en media hora y se guarda al soltar.
 * @param {PointerEvent} evento
 */
function empezarAEstirar(evento) {
  const tirador = /** @type {HTMLElement} */ (evento.currentTarget)
  const desdeY = evento.clientY
  tirador.setPointerCapture(evento.pointerId)
  const maxima = Math.min(props.limite - props.entrada.inicio, JORNADA.duraciones.at(-1) ?? 180)

  /** @param {PointerEvent} movimiento */
  const alMover = (movimiento) => {
    const minutos = props.entrada.duracion + (movimiento.clientY - desdeY) / props.pxPorMinuto
    estirando.value = Math.min(Math.max(Math.round(minutos / JORNADA.paso) * JORNADA.paso, JORNADA.paso), maxima)
  }
  const alSoltar = () => {
    tirador.removeEventListener('pointermove', alMover)
    tirador.removeEventListener('pointerup', alSoltar)
    if (estirando.value !== null && estirando.value !== props.entrada.duracion) {
      emit('duracion', estirando.value)
    }
    estirando.value = null
  }
  tirador.addEventListener('pointermove', alMover)
  tirador.addEventListener('pointerup', alSoltar)
}
</script>

<template>
  <div
    class="bloque"
    :class="{
      'bloque--nota': nota,
      'bloque--terminado': terminada,
      'bloque--bloqueado': incidente?.bloqueo,
      'bloque--ahora': esAhora,
      'bloque--corto': corto,
    }"
    :style="estilo"
    :draggable="!terminada && estirando === null"
    @dragstart="empezarArrastre($event, { tipo: 'entrada', id: entrada.id })"
  >
    <div class="bloque__linea">
      <input
        v-if="nota"
        type="checkbox"
        class="bloque__marca"
        :checked="nota.hecha"
        :aria-label="`Hecha: ${nota.texto}`"
        @change="emit('marcar', /** @type {HTMLInputElement} */ ($event.target).checked)"
      />
      <v-icon
        v-else-if="terminada"
        :icon="iconos.requisitoCumplido"
        size="16"
        class="bloque__hecho"
        aria-hidden="true"
      />
      <router-link
        v-if="incidente"
        :to="{ name: 'incidente', params: { id: incidente.id } }"
        class="bloque__titulo enlace-sutil"
      >
        <CodigoTicket :codigo="incidente.codigo" /> {{ incidente.titulo }}
      </router-link>
      <span v-else class="bloque__titulo">{{ nota?.texto }}</span>
      <v-icon
        v-if="incidente?.bloqueo && corto"
        :icon="iconos.bloqueado"
        size="14"
        class="bloque__candado"
        :aria-label="`Bloqueado: ${incidente.bloqueo}`"
      />
      <span v-if="esAhora" class="bloque__ahora">Ahora</span>
      <span class="bloque__hora num">{{ hora(entrada.inicio) }}–{{ hora(entrada.inicio + duracion) }}</span>
      <span v-if="!terminada" class="bloque__acciones">
        <button
          type="button"
          class="bloque__duracion num"
          :aria-label="`Duración: ${etiquetaDuracion}. Cambiar`"
          @click="cambiarDuracion"
        >
          {{ etiquetaDuracion }}
        </button>
        <v-menu location="bottom end">
          <template #activator="{ props: activador }">
            <v-btn
              v-bind="activador"
              :icon="iconos.masAcciones"
              variant="text"
              size="x-small"
              aria-label="Más opciones"
            />
          </template>
          <v-list density="compact">
            <template v-if="incidente">
              <v-list-item v-if="incidente.bloqueo" title="Quitar el bloqueo" @click="emit('desbloquear')" />
              <v-list-item v-else title="Marcar bloqueado…" @click="emit('bloquear')" />
            </template>
            <v-list-item title="Pasar a mañana" @click="emit('manana')" />
            <v-list-item title="Quitar de hoy" @click="emit('quitar')" />
          </v-list>
        </v-menu>
      </span>
    </div>
    <div
      v-if="!corto && (incidente?.bloqueo || incidente?.vencido || nota?.incidente)"
      class="bloque__linea bloque__detalle"
    >
      <span v-if="incidente?.bloqueo" class="bloque__bloqueo" :title="incidente.bloqueo">
        <v-icon :icon="iconos.bloqueado" size="14" aria-hidden="true" />Bloqueado: {{ incidente.bloqueo }}
      </span>
      <IndicadorDeVencimiento
        v-else-if="incidente?.vencido && !terminada"
        :fecha="incidente.fechaVencimiento"
        :vencido="incidente.vencido"
      />
      <span v-else-if="nota?.incidente">
        Sobre
        <router-link :to="{ name: 'incidente', params: { id: nota.incidente.id } }" class="enlace-sutil">
          <CodigoTicket :codigo="nota.incidente.codigo" />
        </router-link>
      </span>
    </div>
    <span v-if="!terminada" class="bloque__tirador" aria-hidden="true" @pointerdown.prevent.stop="empezarAEstirar" />
  </div>
</template>

<style scoped>
.bloque {
  position: absolute;
  right: 0;
  left: 60px;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 2px 4px 2px 8px;
  overflow: hidden;
  font-size: 13px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-left: 3px solid var(--color);
  border-radius: var(--radio);
  cursor: grab;
  transition: box-shadow var(--rapida);
}

.bloque:hover {
  box-shadow: var(--sombra-1);
}

.bloque--corto {
  justify-content: center;
}

.bloque--nota {
  background: var(--tinte);
  border-color: transparent;
  border-left-color: var(--color);
  border-radius: 8px;
}

.bloque--bloqueado,
.bloque--terminado {
  background: var(--sutil);
}

.bloque--terminado {
  color: var(--tenue);
  cursor: default;
}

.bloque--terminado .bloque__titulo {
  text-decoration: line-through;
  text-decoration-color: var(--borde-fuerte);
}

.bloque--ahora {
  border-color: var(--acento);
  border-left-color: var(--color);
}

.bloque__linea {
  display: flex;
  gap: 8px;
  align-items: center;
  min-width: 0;
  min-height: 22px;
}

.bloque__detalle {
  color: var(--suave);
  font-size: 12.5px;
}

.bloque__titulo {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.bloque__marca {
  flex: none;
  width: 15px;
  height: 15px;
  accent-color: var(--acento);
}

.bloque__hecho {
  color: var(--estado-resuelto);
}

.bloque__candado,
.bloque__bloqueo {
  color: var(--advertencia);
}

.bloque__bloqueo {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  min-width: 0;
  overflow: hidden;
  font-weight: 500;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.bloque__ahora {
  color: var(--acento);
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}

.bloque__hora {
  color: var(--suave);
  font-size: 12px;
  white-space: nowrap;
}

.bloque__acciones {
  display: flex;
  gap: 2px;
  align-items: center;
  opacity: 0;
  transition: opacity var(--rapida);
}

.bloque:hover .bloque__acciones,
.bloque:focus-within .bloque__acciones {
  opacity: 1;
}

.bloque__duracion {
  height: 22px;
  padding: 0 6px;
  color: var(--acento-texto);
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  border-radius: 3px;
}

.bloque__duracion:hover,
.bloque__duracion:focus-visible {
  background: var(--acento-fondo);
  outline: none;
}

.bloque__tirador {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 6px;
  cursor: ns-resize;
}

.bloque__tirador::after {
  position: absolute;
  bottom: 1px;
  left: 50%;
  width: 24px;
  height: 3px;
  margin-left: -12px;
  background: var(--borde-fuerte);
  border-radius: 2px;
  opacity: 0;
  transition: opacity var(--rapida);
  content: '';
}

.bloque:hover .bloque__tirador::after {
  opacity: 1;
}

@media (hover: none) {
  .bloque__acciones,
  .bloque__tirador::after {
    opacity: 1;
  }
}
</style>
