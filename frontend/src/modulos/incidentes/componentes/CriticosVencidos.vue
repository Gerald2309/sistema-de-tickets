<script setup>
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { FILTROS_CRITICOS_VENCIDOS } from '../composables/usePanel.js'
import { rutaDelListado } from '../filtros.js'
import CodigoTicket from './CodigoTicket.vue'
import IndicadorDeVencimiento from './IndicadorDeVencimiento.vue'

/** Los incidentes de prioridad crítica fuera de plazo, primero los que más tiempo llevan vencidos. */
defineProps({
  pagina: {
    type: /** @type {import('vue').PropType<import('@/compartido/api/paginacion.js').Pagina<import('../tipos.js').Incidente> | undefined>} */ (
      Object
    ),
    default: undefined,
  },
  cargando: { type: Boolean, default: false },
})

const { por: _por, ...filtrosDelListado } = FILTROS_CRITICOS_VENCIDOS
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-criticos">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-criticos" class="tarjeta__titulo">Críticos vencidos</h2>
      <RouterLink v-if="pagina?.total" :to="rutaDelListado(filtrosDelListado)" class="enlace-sutil">
        Ver los {{ pagina.total }}
      </RouterLink>
    </header>
    <p class="criticos__frase">Prioridad crítica y fuera de plazo; primero los que más tiempo llevan vencidos.</p>
    <ul v-if="cargando" class="criticos" aria-busy="true">
      <li v-for="fila in 4" :key="fila" class="critico"><EsqueletoDeCarga :ancho="`${60 + fila * 8}%`" /></li>
    </ul>
    <ul v-else-if="pagina?.elementos.length" class="criticos">
      <li v-for="incidente in pagina.elementos" :key="incidente.id">
        <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="critico">
          <CodigoTicket :codigo="incidente.codigo" />
          <span class="critico__titulo">{{ incidente.titulo }}</span>
          <span class="critico__persona" :class="{ 'critico__persona--libre': !incidente.asignado }">
            {{ incidente.asignado?.nombre ?? 'Sin asignar' }}
          </span>
          <IndicadorDeVencimiento :fecha="incidente.fechaVencimiento" :vencido="incidente.vencido" />
        </RouterLink>
      </li>
    </ul>
    <p v-else class="criticos__vacio">Ningún incidente crítico está vencido.</p>
  </section>
</template>

<style scoped>
.criticos__frase {
  margin: 0;
  padding: 12px 16px 4px;
  color: var(--suave);
  font-size: 13px;
}

.criticos {
  margin: 0;
  padding: 4px 8px 10px;
  list-style: none;
}

.critico {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto auto;
  gap: 12px;
  align-items: center;
  min-height: 40px;
  padding: 0 8px;
  color: var(--texto);
  font-size: 13.5px;
  text-decoration: none;
  border-radius: var(--radio);
  transition: background-color var(--rapida);
}

.critico:hover {
  background: var(--sutil);
}

.critico__titulo {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.critico__persona {
  color: var(--suave);
  font-size: 12.5px;
  white-space: nowrap;
}

.critico__persona--libre {
  font-style: italic;
}

.criticos__vacio {
  margin: 0;
  padding: 20px 16px 24px;
  color: var(--suave);
  text-align: center;
}

@media (width <= 760px) {
  .critico {
    grid-template-columns: auto minmax(0, 1fr) auto;
  }

  .critico__persona {
    display: none;
  }
}
</style>
