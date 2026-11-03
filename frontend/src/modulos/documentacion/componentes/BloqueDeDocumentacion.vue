<script setup>
import { computed, ref } from 'vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import PlanoTecnico from './PlanoTecnico.vue'
import RecorridoPorPasos from './RecorridoPorPasos.vue'
import ReferenciaDeApi from './ReferenciaDeApi.vue'
import SimuladorDeRegla from './SimuladorDeRegla.vue'
import TextoTecnico from './TextoTecnico.vue'

/** Dibuja un bloque de contenido según su tipo (tipos.js). */
const props = defineProps({
  bloque: { type: /** @type {import('vue').PropType<import('../tipos.js').Bloque>} */ (Object), required: true },
  // Lo que pide la dirección al llegar desde el buscador: la fila a destacar y el paso a abrir.
  fila: { type: Number, default: undefined },
  paso: { type: Number, default: 0 },
})

// Planos alternables y focos de un plano: cuál está elegido.
const elegido = ref('0')
const opciones = computed(() => {
  if (props.bloque.tipo === 'planos')
    return props.bloque.vistas.map((vista, i) => ({ valor: `${i}`, etiqueta: vista.etiqueta }))
  if (props.bloque.tipo === 'focos')
    return props.bloque.focos.map((foco, i) => ({ valor: `${i}`, etiqueta: foco.etiqueta }))
  return []
})
</script>

<template>
  <dl v-if="bloque.tipo === 'filas'" class="filas">
    <div
      v-for="([titulo, texto], i) in bloque.filas"
      :key="titulo"
      class="filas__fila"
      :class="{ destacada: i === fila }"
    >
      <dt><TextoTecnico :texto="titulo" /></dt>
      <dd><TextoTecnico :texto="texto" /></dd>
    </div>
  </dl>

  <pre v-else-if="bloque.tipo === 'codigo'" class="codigo">{{ bloque.codigo }}</pre>

  <p v-else-if="bloque.tipo === 'nota'" class="nota-tecnica"><TextoTecnico :texto="bloque.texto" /></p>

  <img v-else-if="bloque.tipo === 'imagen'" :src="bloque.src" :alt="bloque.alt" class="imagen" loading="lazy" />

  <PlanoTecnico v-else-if="bloque.tipo === 'plano'" :plano="bloque.plano" />

  <template v-else-if="bloque.tipo === 'planos'">
    <div class="selector"><ControlSegmentado v-model="elegido" :opciones="opciones" etiqueta="Nivel del plano" /></div>
    <PlanoTecnico :plano="bloque.vistas[Number(elegido)].plano" />
    <p class="nota-tecnica"><TextoTecnico :texto="bloque.vistas[Number(elegido)].nota" /></p>
  </template>

  <template v-else-if="bloque.tipo === 'focos'">
    <div class="selector"><ControlSegmentado v-model="elegido" :opciones="opciones" etiqueta="Camino" /></div>
    <PlanoTecnico :plano="bloque.plano" :iluminados="bloque.focos[Number(elegido)]" />
    <p class="nota-tecnica"><TextoTecnico :texto="bloque.focos[Number(elegido)].nota" /></p>
  </template>

  <RecorridoPorPasos
    v-else-if="bloque.tipo === 'recorrido'"
    :plano="bloque.plano"
    :pasos="bloque.pasos"
    :paso-inicial="paso"
  />

  <SimuladorDeRegla v-else-if="bloque.tipo === 'simulador'" />

  <ReferenciaDeApi v-else-if="bloque.tipo === 'api'" />
</template>

<style scoped>
.filas {
  margin: 0;
}

.filas__fila {
  display: grid;
  grid-template-columns: minmax(160px, 220px) 1fr;
  gap: 16px;
  padding: 12px 16px;
  font-size: 14px;
  line-height: 1.55;
}

.filas__fila + .filas__fila {
  border-top: 1px solid var(--borde);
}

.filas dt {
  font-weight: 600;
}

.filas dd {
  margin: 0;
  color: var(--suave);
}

.destacada {
  animation: destacar 1.8s ease-out;
}

.codigo {
  margin: 0;
  padding: 14px 16px;
  overflow-x: auto;
  font: 12px/1.65 var(--mono);
  font-variant-ligatures: none;
  background: var(--sutil);
  border-bottom: 1px solid var(--borde);
}

.nota-tecnica {
  margin: 0;
  padding: 12px 16px;
  color: var(--suave);
  font-size: 13.5px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.imagen {
  display: block;
  width: 100%;
  border-bottom: 1px solid var(--borde);
}

.selector {
  padding: 12px 16px;
  border-bottom: 1px solid var(--borde);
}

@keyframes destacar {
  0%,
  30% {
    background: var(--advertencia-fondo);
  }

  100% {
    background: transparent;
  }
}

@media (width <= 760px) {
  .filas__fila {
    grid-template-columns: 1fr;
    gap: 2px;
  }
}
</style>
