<script setup>
import { ref } from 'vue'

/**
 * Vistas de una lista en control segmentado (sistema de diseño, Barra de herramientas de listas). Sigue el
 * patrón de pestañas de WAI-ARIA: las flechas recorren las opciones y solo la activa entra en el orden de Tab.
 */
const props = defineProps({
  opciones: {
    type: /** @type {import('vue').PropType<{ valor: string, etiqueta: string, conteo?: number }[]>} */ (Array),
    required: true,
  },
  etiqueta: { type: String, required: true },
})
const modelo = defineModel({ type: String, required: true })
const botones = ref(/** @type {HTMLButtonElement[]} */ ([]))

/**
 * @param {KeyboardEvent} evento
 * @param {number} indice
 * @returns {void}
 */
function alPulsarTecla(evento, indice) {
  const pasos = { ArrowRight: 1, ArrowLeft: -1, Home: -indice, End: props.opciones.length - 1 - indice }
  const paso = pasos[/** @type {keyof typeof pasos} */ (evento.key)]
  if (paso === undefined) {
    return
  }
  evento.preventDefault()
  const destino = (indice + paso + props.opciones.length) % props.opciones.length
  modelo.value = props.opciones[destino].valor
  botones.value[destino]?.focus()
}
</script>

<template>
  <div class="segmento" role="tablist" :aria-label="etiqueta">
    <button
      v-for="(opcion, indice) in opciones"
      :key="opcion.valor"
      ref="botones"
      type="button"
      role="tab"
      class="segmento__opcion"
      :class="{ 'segmento__opcion--activa': modelo === opcion.valor }"
      :aria-selected="modelo === opcion.valor"
      :tabindex="modelo === opcion.valor ? 0 : -1"
      @click="modelo = opcion.valor"
      @keydown="alPulsarTecla($event, indice)"
    >
      {{ opcion.etiqueta
      }}<span v-if="opcion.conteo !== undefined" class="segmento__conteo num">{{ opcion.conteo }}</span>
    </button>
  </div>
</template>

<style scoped>
.segmento {
  display: inline-flex;
  padding: 2px;
  background: var(--resalte);
  border-radius: 5px;
}

.segmento__opcion {
  display: flex;
  gap: 6px;
  align-items: center;
  padding: 5px 11px;
  color: var(--suave);
  font: 600 13px var(--fuente);
  background: transparent;
  border: 0;
  border-radius: var(--radio);
  cursor: pointer;
  transition:
    background-color 0.2s var(--curva),
    color 0.2s,
    box-shadow 0.2s;
}

.segmento__opcion:hover {
  color: var(--texto);
}

.segmento__opcion--activa {
  color: var(--texto);
  background: var(--superficie);
  box-shadow: var(--sombra-1);
}

.segmento__conteo {
  color: var(--tenue);
  font-weight: 500;
}

.segmento__opcion--activa .segmento__conteo {
  color: var(--acento-texto);
}
</style>
