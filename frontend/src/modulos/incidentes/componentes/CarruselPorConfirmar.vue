<script setup>
import { computed, ref, watch } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import TarjetaPorConfirmar from './TarjetaPorConfirmar.vue'

/**
 * Las soluciones por confirmar, de a una, en una tarjeta plana: «1 de 8» y flechas para recorrerlas. Al
 * responder una, sale de la lista y queda a la vista la siguiente. Sin elementos no se muestra.
 */
const props = defineProps({
  elementos: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente[]>} */ (Array), required: true },
  pendienteDe: { type: /** @type {import('vue').PropType<(id: number) => boolean>} */ (Function), required: true },
})
const emit = defineEmits(['confirmar'])

const indice = ref(0)
// El sentido decide hacia dónde se desliza la tarjeta.
const sentido = ref(/** @type {'adelante' | 'atras'} */ ('adelante'))
const actual = computed(() => props.elementos[indice.value])

// Si la lista se acorta (se respondió la última), se queda en la que ahora es la última.
watch(
  () => props.elementos.length,
  (largo) => (indice.value = Math.min(indice.value, Math.max(largo - 1, 0))),
)

/** @param {1 | -1} paso */
function mover(paso) {
  sentido.value = paso > 0 ? 'adelante' : 'atras'
  indice.value = (indice.value + paso + props.elementos.length) % props.elementos.length
}
</script>

<template>
  <section v-if="actual" class="tarjeta carrusel" aria-roledescription="carrusel" aria-labelledby="titulo-carrusel">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-carrusel" class="tarjeta__titulo">
        <span class="punto carrusel__punto" aria-hidden="true" />Confirme si quedó solucionado
      </h2>
      <div class="carrusel__navegacion">
        <span class="nota num" aria-live="polite">{{ indice + 1 }} de {{ elementos.length }}</span>
        <template v-if="elementos.length > 1">
          <v-btn :icon="iconos.anterior" variant="text" size="small" aria-label="Anterior" @click="mover(-1)" />
          <v-btn :icon="iconos.siguiente" variant="text" size="small" aria-label="Siguiente" @click="mover(1)" />
        </template>
      </div>
    </header>
    <div class="tarjeta__cuerpo carrusel__cuerpo">
      <Transition :name="`deslizar-${sentido}`" mode="out-in">
        <TarjetaPorConfirmar
          :key="actual.id"
          :incidente="actual"
          :pendiente="pendienteDe(actual.id)"
          role="group"
          aria-roledescription="diapositiva"
          :aria-label="`${indice + 1} de ${elementos.length}`"
          @confirmar="(respuesta) => emit('confirmar', actual.id, respuesta)"
        />
      </Transition>
    </div>
  </section>
</template>

<style scoped>
.carrusel__punto {
  margin-right: 8px;
  background: var(--estado-resuelto);
}

.carrusel__navegacion {
  display: flex;
  gap: 2px;
  align-items: center;
}

.carrusel__navegacion .nota {
  margin-right: 6px;
}

.carrusel__cuerpo {
  overflow: hidden;
}

/* Solo transform y opacidad, con la curva del sistema (fundamentos de movimiento). */
.deslizar-adelante-enter-active,
.deslizar-adelante-leave-active,
.deslizar-atras-enter-active,
.deslizar-atras-leave-active {
  transition:
    transform var(--rapida) var(--curva),
    opacity var(--rapida) var(--curva);
}

.deslizar-adelante-enter-from,
.deslizar-atras-leave-to {
  opacity: 0;
  transform: translateX(16px);
}

.deslizar-adelante-leave-to,
.deslizar-atras-enter-from {
  opacity: 0;
  transform: translateX(-16px);
}

@media (prefers-reduced-motion: reduce) {
  .deslizar-adelante-enter-active,
  .deslizar-adelante-leave-active,
  .deslizar-atras-enter-active,
  .deslizar-atras-leave-active {
    transition: none;
  }
}
</style>
