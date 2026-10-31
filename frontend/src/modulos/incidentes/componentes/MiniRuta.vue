<script setup>
import { computed } from 'vue'
import { PASOS_DE_LA_SOLICITUD, nombreDelPaso, pasoDeLaSolicitud } from '../presentacion.js'

/**
 * La ruta de «Su solicitud» en pequeño, para una fila: lo recorrido en verde y continuo, lo que falta
 * punteado, y el paso actual con su nombre debajo.
 */
const props = defineProps({
  incidente: {
    type: /** @type {import('vue').PropType<Pick<import('../tipos.js').Incidente, 'estado' | 'asignado'>>} */ (Object),
    required: true,
  },
})

const actual = computed(() => pasoDeLaSolicitud(props.incidente))
const nombre = computed(() => nombreDelPaso(props.incidente))
// Cerrada es el final del camino: ya no hay «está aquí», todo está recorrido.
const terminada = computed(() => actual.value === PASOS_DE_LA_SOLICITUD.length - 1)
</script>

<template>
  <span class="mini-ruta" :aria-label="`Paso ${actual + 1} de ${PASOS_DE_LA_SOLICITUD.length}: ${nombre}`">
    <span class="mini-ruta__camino" aria-hidden="true">
      <template v-for="(paso, indice) in PASOS_DE_LA_SOLICITUD" :key="paso">
        <span
          class="mini-ruta__paso"
          :class="{
            'mini-ruta__paso--hecho': indice < actual || (terminada && indice === actual),
            'mini-ruta__paso--actual': indice === actual && !terminada,
          }"
        />
        <span
          v-if="indice < PASOS_DE_LA_SOLICITUD.length - 1"
          class="mini-ruta__tramo"
          :class="{ 'mini-ruta__tramo--hecho': indice < actual }"
        />
      </template>
    </span>
    <span class="mini-ruta__nombre" aria-hidden="true">{{ nombre }}</span>
  </span>
</template>

<style scoped>
.mini-ruta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.mini-ruta__camino {
  display: flex;
  align-items: center;
}

.mini-ruta__paso {
  flex: none;
  width: 10px;
  height: 10px;
  background: var(--superficie);
  border: 2px solid var(--borde-fuerte);
  border-radius: 50%;
}

.mini-ruta__paso--hecho {
  background: var(--estado-resuelto);
  border-color: var(--estado-resuelto);
}

.mini-ruta__paso--actual {
  border-color: var(--acento);
  box-shadow: 0 0 0 3px var(--acento-fondo);
}

.mini-ruta__tramo {
  flex: 1;
  border-top: 2px dashed var(--borde-fuerte);
}

.mini-ruta__tramo--hecho {
  border-top-style: solid;
  border-top-color: var(--estado-resuelto);
}

.mini-ruta__nombre {
  overflow: hidden;
  color: var(--suave);
  font-size: 12px;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
