<script setup>
import { computed, ref, watch } from 'vue'
import PlanoTecnico from './PlanoTecnico.vue'
import TextoTecnico from './TextoTecnico.vue'
import { iconos } from '@/compartido/iconos.js'
import { enlaceAlCodigo } from '../repositorio.js'

/**
 * Un recorrido sobre un plano: cada paso ilumina sus cajas y flechas y muestra el archivo, la explicación y el
 * código real. Se avanza con los botones o eligiendo el paso en la lista.
 */
const props = defineProps({
  plano: { type: /** @type {import('vue').PropType<import('../tipos.js').Plano>} */ (Object), required: true },
  pasos: { type: /** @type {import('vue').PropType<import('../tipos.js').Paso[]>} */ (Array), required: true },
  // El paso que pide la dirección (?paso=), al llegar desde el buscador.
  pasoInicial: { type: Number, default: 0 },
})

const CAPAS = { front: 'Front', back: 'Back', datos: 'Datos', infra: 'Infra' }
const actual = ref(props.pasoInicial)
const paso = computed(() => props.pasos[actual.value])
const iluminados = computed(() => ({ nodos: paso.value.nodos, aristas: paso.value.aristas }))

watch(
  () => props.pasoInicial,
  (indice) => {
    actual.value = indice
  },
)
</script>

<template>
  <div>
    <PlanoTecnico :plano="plano" :iluminados="iluminados" />
    <div class="recorrido">
      <section class="detalle" aria-live="polite">
        <h3 class="detalle__titulo">{{ actual + 1 }} · {{ paso.titulo }}</h3>
        <p class="detalle__texto"><TextoTecnico :texto="paso.texto" /></p>
        <p class="detalle__archivo">
          <a
            v-if="enlaceAlCodigo(paso.archivo)"
            :href="enlaceAlCodigo(paso.archivo) ?? undefined"
            target="_blank"
            rel="noopener"
            >{{ paso.archivo }} <v-icon :icon="iconos.abrirAparte" size="12"
          /></a>
          <template v-else>{{ paso.archivo }}</template>
        </p>
        <pre v-if="paso.codigo" class="detalle__codigo">{{ paso.codigo }}</pre>
        <div class="detalle__navegar">
          <span class="detalle__contador num">{{ actual + 1 }} / {{ pasos.length }}</span>
          <v-btn variant="outlined" :disabled="actual === 0" @click="actual--">Anterior</v-btn>
          <v-btn color="primary" variant="flat" :disabled="actual === pasos.length - 1" @click="actual++"
            >Siguiente</v-btn
          >
        </div>
      </section>
      <ol class="pasos" aria-label="Pasos">
        <li v-for="(unPaso, i) in pasos" :key="unPaso.titulo">
          <button
            type="button"
            class="pasos__boton"
            :aria-current="i === actual ? 'step' : undefined"
            @click="actual = i"
          >
            <span class="pasos__numero num">{{ i + 1 }}</span>
            <span>{{ unPaso.titulo }}</span>
            <span class="pasos__capa" :class="`pasos__capa--${unPaso.capa}`">{{ CAPAS[unPaso.capa] }}</span>
          </button>
        </li>
      </ol>
    </div>
  </div>
</template>

<style scoped>
.recorrido {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
  border-top: 1px solid var(--borde);
}

.detalle {
  border-right: 1px solid var(--borde);
}

.detalle__titulo {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 700;
  border-bottom: 1px solid var(--borde);
}

.detalle__texto {
  margin: 0;
  padding: 14px 16px 4px;
  color: var(--suave);
  font-size: 14px;
  line-height: 1.6;
}

.detalle__archivo {
  margin: 0;
  padding: 4px 16px 12px;
  color: var(--tenue);
  font: 11.5px var(--mono);
  overflow-wrap: anywhere;
}

.detalle__archivo a {
  color: var(--acento);
  text-decoration: none;
}

.detalle__codigo {
  margin: 0;
  padding: 12px 16px;
  overflow-x: auto;
  font: 11.5px/1.65 var(--mono);
  font-variant-ligatures: none;
  white-space: pre-wrap;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.detalle__navegar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 12px 16px;
  border-top: 1px solid var(--borde);
}

.detalle__contador {
  margin-right: auto;
  color: var(--tenue);
  font: 12px var(--mono);
}

.pasos {
  margin: 0;
  padding: 0;
  list-style: none;
}

.pasos li + li {
  border-top: 1px solid var(--borde);
}

.pasos__boton {
  display: grid;
  grid-template-columns: 26px 1fr auto;
  gap: 10px;
  align-items: center;
  width: 100%;
  padding: 10px 16px;
  color: var(--texto);
  font: 13.5px var(--fuente);
  text-align: left;
  background: var(--superficie);
  border: 0;
  cursor: pointer;
}

.pasos__boton:hover {
  background: var(--sutil);
}

.pasos__boton[aria-current] {
  background: var(--acento-fondo);
  box-shadow: inset 3px 0 0 var(--acento);
}

.pasos__numero {
  display: grid;
  place-items: center;
  width: 22px;
  height: 22px;
  color: var(--suave);
  font-size: 11px;
  font-weight: 600;
  background: var(--resalte);
  border-radius: 50%;
}

.pasos__boton[aria-current] .pasos__numero {
  color: var(--superficie);
  background: var(--acento);
}

.pasos__capa {
  padding: 2px 8px;
  font: 600 10.5px var(--mono);
  text-transform: uppercase;
  border-radius: var(--radio-chip);
}

.pasos__capa--front {
  color: var(--estado-abierto-texto);
  background: var(--estado-abierto-fondo);
}

.pasos__capa--back {
  color: var(--estado-resuelto-texto);
  background: var(--estado-resuelto-fondo);
}

.pasos__capa--datos,
.pasos__capa--infra {
  color: var(--estado-en-progreso-texto);
  background: var(--estado-en-progreso-fondo);
}

@media (width <= 900px) {
  .recorrido {
    grid-template-columns: 1fr;
  }

  .detalle {
    border-right: 0;
    border-bottom: 1px solid var(--borde);
  }
}
</style>
