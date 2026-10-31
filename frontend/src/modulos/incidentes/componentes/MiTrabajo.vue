<script setup>
import { rutaDelListado } from '../filtros.js'

/** Accesos al trabajo propio con su conteo (RF-F16). Cada uno abre el listado con ese filtro. */
defineProps({
  accesos: {
    type: /** @type {import('vue').PropType<{ titulo: string, icono: string, cantidad?: number, urgente?: boolean, filtros: import('../tipos.js').FiltrosDeIncidentes }[]>} */ (
      Array
    ),
    required: true,
  },
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-mi-trabajo">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-mi-trabajo" class="tarjeta__titulo">Mi trabajo</h2>
    </header>
    <RouterLink v-for="acceso in accesos" :key="acceso.titulo" :to="rutaDelListado(acceso.filtros)" class="acceso">
      <v-icon :icon="acceso.icono" size="18" class="acceso__icono" />
      {{ acceso.titulo }}
      <b class="acceso__cantidad num" :class="{ 'acceso__cantidad--urgente': acceso.urgente && acceso.cantidad }">
        {{ acceso.cantidad ?? '…' }}
      </b>
    </RouterLink>
  </section>
</template>

<style scoped>
.acceso {
  display: flex;
  gap: 10px;
  align-items: center;
  padding: 11px 16px;
  color: var(--texto);
  font-size: 13.5px;
  text-decoration: none;
  border-bottom: 1px solid var(--borde);
  transition: background-color var(--rapida);
}

.acceso:last-child {
  border-bottom: 0;
}

.acceso:hover {
  background: var(--sutil);
}

.acceso__icono {
  color: var(--suave);
}

.acceso__cantidad {
  margin-left: auto;
  font-size: 15px;
}

.acceso__cantidad--urgente {
  color: var(--error);
}
</style>
