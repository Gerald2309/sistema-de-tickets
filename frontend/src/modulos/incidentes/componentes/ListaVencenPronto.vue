<script setup>
import AvatarPersona from '@/compartido/componentes/AvatarPersona.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { rutaDelListado } from '../filtros.js'
import CodigoTicket from './CodigoTicket.vue'
import InsigniaPrioridad from './InsigniaPrioridad.vue'
import IndicadorDeVencimiento from './IndicadorDeVencimiento.vue'

/** Los pendientes que vencen en las próximas 24 horas, los más urgentes primero (RF-F15). */
defineProps({
  pagina: {
    type: /** @type {import('vue').PropType<import('@/compartido/api/paginacion.js').Pagina<import('../tipos.js').Incidente> | undefined>} */ (
      Object
    ),
    default: undefined,
  },
  cargando: { type: Boolean, default: false },
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-vencen">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-vencen" class="tarjeta__titulo">Vencen en las próximas 24 horas</h2>
      <RouterLink
        v-if="pagina?.total"
        :to="rutaDelListado({ vence: 24, orden: 'fecha_vencimiento', dir: 'asc' })"
        class="enlace-sutil"
      >
        Ver los {{ pagina.total }}
      </RouterLink>
    </header>
    <v-table class="tabla-panel">
      <tbody v-if="cargando">
        <tr v-for="fila in 5" :key="fila">
          <td><EsqueletoDeCarga ancho="84px" /></td>
          <td><EsqueletoDeCarga :ancho="`${140 + fila * 20}px`" /></td>
          <td><EsqueletoDeCarga ancho="64px" /></td>
          <td><EsqueletoDeCarga ancho="96px" /></td>
          <td><EsqueletoDeCarga ancho="60px" /></td>
        </tr>
      </tbody>
      <tbody v-else-if="pagina?.elementos.length">
        <tr v-for="incidente in pagina.elementos" :key="incidente.id">
          <td class="columna-codigo"><CodigoTicket :codigo="incidente.codigo" /></td>
          <td class="columna-titulo">
            <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="enlace-fila">
              {{ incidente.titulo }}
            </RouterLink>
          </td>
          <td><InsigniaPrioridad :prioridad="incidente.prioridad" /></td>
          <td>
            <AvatarPersona v-if="incidente.asignado" :nombre="incidente.asignado.nombre" />
            <span v-else class="tenue">Sin asignar</span>
          </td>
          <td><IndicadorDeVencimiento :fecha="incidente.fechaVencimiento" :vencido="incidente.vencido" /></td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr>
          <td colspan="5" class="tabla-panel__vacia">Nada vence en las próximas 24 horas.</td>
        </tr>
      </tbody>
    </v-table>
  </section>
</template>

<style scoped>
.columna-codigo {
  width: 110px;
}

.columna-titulo {
  width: 100%;
  max-width: 0;
}

.enlace-fila {
  display: block;
  overflow: hidden;
  color: var(--texto);
  font-weight: 500;
  white-space: nowrap;
  text-overflow: ellipsis;
  text-decoration: none;
}

.enlace-fila:hover {
  color: var(--acento);
}

.tenue {
  color: var(--tenue);
}

.tabla-panel__vacia {
  padding: 24px 16px !important;
  color: var(--suave);
  text-align: center;
}
</style>
