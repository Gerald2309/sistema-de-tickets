<script setup>
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import CodigoTicket from './CodigoTicket.vue'
import InsigniaEstado from './InsigniaEstado.vue'

/**
 * Solicitudes en filas de una línea: código y título, respuestas sin leer, estado, quién la atiende y cuándo se
 * movió. Densa a propósito: la acción destacada está arriba, aquí se busca y se abre.
 */
defineProps({
  elementos: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente[]>} */ (Array), required: true },
  vacio: { type: String, required: true },
})

/** @param {number} cantidad */
const nuevos = (cantidad) => (cantidad === 1 ? '1 mensaje nuevo' : `${cantidad} mensajes nuevos`)
</script>

<template>
  <p v-if="!elementos.length" class="nota lista-solicitudes__vacia">{{ vacio }}</p>
  <ul v-else class="lista-solicitudes">
    <li v-for="incidente in elementos" :key="incidente.id">
      <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="fila-solicitud">
        <CodigoTicket :codigo="incidente.codigo" class="fila-solicitud__codigo" />
        <span class="fila-solicitud__titulo">{{ incidente.titulo }}</span>
        <span v-if="incidente.mensajesSinLeer" class="fila-solicitud__nuevos"
          ><span class="punto fila-solicitud__punto" aria-hidden="true" />{{ nuevos(incidente.mensajesSinLeer) }}</span
        >
        <InsigniaEstado :estado="incidente.estado" class="fila-solicitud__estado" />
        <span class="nota fila-solicitud__quien">{{ incidente.asignado?.nombre ?? 'Esperando a soporte' }}</span>
        <span class="nota fila-solicitud__cuando">{{ tiempoRelativo(incidente.actualizadoEn).texto }}</span>
      </RouterLink>
    </li>
  </ul>
</template>

<style scoped>
.lista-solicitudes {
  margin: 0;
  padding: 0;
  list-style: none;
}

.lista-solicitudes__vacia {
  margin: 0;
  padding: 20px 16px;
}

.fila-solicitud {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto 120px 150px 90px;
  gap: 12px;
  align-items: center;
  padding: 10px 16px;
  color: var(--texto);
  text-decoration: none;
  border-top: 1px solid var(--borde);
  transition: background-color var(--rapida);
}

.fila-solicitud:hover {
  background: var(--sutil);
}

.fila-solicitud__titulo,
.fila-solicitud__quien {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* Punto y texto, como las insignias del sistema: nunca píldora de color. */
.fila-solicitud__nuevos {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  color: var(--acento);
  font-size: 12.5px;
  font-weight: 600;
  white-space: nowrap;
}

.fila-solicitud__punto {
  background: var(--acento);
}

.fila-solicitud__cuando {
  text-align: right;
}

/* En pantallas angostas quedan código, título, respuestas nuevas y estado; el detalle dice el resto. */
@media (width <= 720px) {
  .fila-solicitud {
    grid-template-columns: auto minmax(0, 1fr) auto auto;
  }

  .fila-solicitud__quien,
  .fila-solicitud__cuando {
    display: none;
  }
}
</style>
