<script setup>
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import CodigoTicket from './CodigoTicket.vue'

/** Casos resueltos parecidos, con su solución, para no resolver dos veces lo mismo (RF-F31). */
defineProps({
  casos: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Similar[] | undefined>} */ (Array),
    default: undefined,
  },
  cargando: { type: Boolean, default: false },
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-similares">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-similares" class="tarjeta__titulo">Casos similares resueltos</h2>
      <span v-if="casos?.length" class="nota"
        >{{ casos.length }} {{ casos.length === 1 ? 'coincidencia' : 'coincidencias' }}</span
      >
    </header>
    <div v-if="cargando" class="tarjeta__cuerpo"><EsqueletoDeCarga ancho="70%" /></div>
    <p v-else-if="!casos?.length" class="tarjeta__cuerpo nota similares__vacio">No hay casos similares resueltos.</p>
    <ul v-else class="similares">
      <li v-for="caso in casos" :key="caso.id" class="similar">
        <div class="similar__cabecera">
          <RouterLink :to="{ name: 'incidente', params: { id: caso.id } }" class="similar__titulo">{{
            caso.titulo
          }}</RouterLink>
          <CodigoTicket :codigo="caso.codigo" />
        </div>
        <p v-if="caso.solucion" class="similar__solucion">{{ caso.solucion }}</p>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.similares {
  margin: 0;
  padding: 0;
  list-style: none;
}

.similar {
  padding: 14px 16px;
}

.similar + .similar {
  border-top: 1px solid var(--borde);
}

.similar__cabecera {
  display: flex;
  gap: 12px;
  justify-content: space-between;
}

.similar__titulo {
  color: var(--texto);
  font-weight: 500;
  text-decoration: none;
}

.similar__titulo:hover {
  color: var(--acento);
}

.similar__solucion {
  display: -webkit-box;
  margin: 4px 0 0;
  overflow: hidden;
  color: var(--suave);
  font-size: 13.5px;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
}

.similares__vacio {
  margin: 0;
}
</style>
