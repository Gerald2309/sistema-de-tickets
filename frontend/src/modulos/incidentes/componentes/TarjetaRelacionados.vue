<script setup>
import { presentarEstado } from '../presentacion.js'
import CodigoTicket from './CodigoTicket.vue'

/**
 * Lista corta de incidentes relacionados con enlace al listado completo: los otros del solicitante (RF-F32).
 */
defineProps({
  titulo: { type: String, required: true },
  elementos: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente[]>} */ (Array), required: true },
  total: { type: Number, required: true },
  enlace: {
    type: /** @type {import('vue').PropType<import('vue-router').RouteLocationRaw>} */ ([Object, String]),
    required: true,
  },
  textoDelEnlace: { type: String, required: true },
  vacio: { type: String, required: true },
})
</script>

<template>
  <section class="tarjeta">
    <header class="tarjeta__cabecera">
      <h2 class="tarjeta__titulo">{{ titulo }}</h2>
      <span class="nota">{{ total }}</span>
    </header>
    <p v-if="!elementos.length" class="tarjeta__cuerpo nota relacionados__vacio">{{ vacio }}</p>
    <template v-else>
      <RouterLink
        v-for="incidente in elementos"
        :key="incidente.id"
        :to="{ name: 'incidente', params: { id: incidente.id } }"
        class="relacionado"
      >
        <span class="relacionado__punto" :style="{ background: presentarEstado(incidente.estado).color }" />
        <span class="solo-lectores">{{ presentarEstado(incidente.estado).nombre }}:</span>
        <CodigoTicket :codigo="incidente.codigo" />
        <span class="relacionado__titulo">{{ incidente.titulo }}</span>
      </RouterLink>
      <RouterLink :to="enlace" class="relacionados__pie">{{ textoDelEnlace }} →</RouterLink>
    </template>
  </section>
</template>

<style scoped>
.relacionado {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 9px 16px;
  color: var(--texto);
  font-size: 13px;
  text-decoration: none;
  border-bottom: 1px solid var(--borde);
  transition: background-color var(--rapida);
}

.relacionado:hover {
  background: var(--sutil);
}

.relacionado__punto {
  flex: none;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.relacionado__titulo {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.relacionados__pie {
  display: block;
  padding: 9px 16px;
  color: var(--acento);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
}

.relacionados__pie:hover {
  text-decoration: underline;
}

.relacionados__vacio {
  margin: 0;
}
</style>
