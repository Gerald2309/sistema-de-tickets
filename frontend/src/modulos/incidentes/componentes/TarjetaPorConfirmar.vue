<script setup>
import { useQuery } from '@tanstack/vue-query'
import { computed } from 'vue'
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import { consultaDeDetalle } from '../composables/useIncidente.js'
import CodigoTicket from './CodigoTicket.vue'
import ConfirmarSolucion from './ConfirmarSolucion.vue'

/**
 * Una solución por confirmar, dentro del carrusel del inicio. El listado no trae los textos largos: la solución
 * sale del detalle, que queda en caché para cuando la persona abra el ticket.
 */
const props = defineProps({
  incidente: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente>} */ (Object), required: true },
  pendiente: { type: Boolean, default: false },
})
const emit = defineEmits(['confirmar'])

const detalle = useQuery(consultaDeDetalle(props.incidente.id))
const solucion = computed(() => detalle.data.value?.solucion)
</script>

<template>
  <div class="por-confirmar">
    <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="por-confirmar__titulo">
      <CodigoTicket :codigo="incidente.codigo" /> {{ incidente.titulo }}
    </RouterLink>
    <p class="nota por-confirmar__quien">
      {{ incidente.asignado ? `La resolvió ${incidente.asignado.nombre}` : 'Resuelta' }} ·
      {{ tiempoRelativo(incidente.actualizadoEn).texto }}
    </p>
    <p class="por-confirmar__solucion">
      <template v-if="solucion">{{ solucion }}</template>
      <span v-else class="tenue">Cargando la solución…</span>
    </p>
    <ConfirmarSolucion :pendiente="pendiente" @confirmar="(respuesta) => emit('confirmar', respuesta)" />
  </div>
</template>

<style scoped>
.por-confirmar {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.por-confirmar__titulo {
  overflow: hidden;
  color: var(--texto);
  font-weight: 600;
  white-space: nowrap;
  text-decoration: none;
  text-overflow: ellipsis;
}

.por-confirmar__titulo:hover {
  text-decoration: underline;
}

.por-confirmar__quien {
  margin: 0;
}

.por-confirmar__solucion {
  display: -webkit-box;
  margin: 6px 0 10px;
  overflow: hidden;
  font-size: 13.5px;
  line-height: 1.55;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}
</style>
