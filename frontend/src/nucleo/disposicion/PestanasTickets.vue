<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { iconos } from '@/compartido/iconos.js'
import { presentarEstado, usePestanas } from '@/modulos/incidentes/index.js'

/**
 * Pestañas de tickets abiertos, como las del navegador (RF-F11, F-09). «Incidentes» es fija y vuelve al
 * listado con los últimos filtros. Patrón de pestañas de WAI-ARIA con activación manual: las flechas mueven
 * el foco, Intro o clic abren, Supr o el clic central cierran.
 */
const pestanas = usePestanas()
const ruta = useRoute()
const enrutador = useRouter()
const barra = ref(/** @type {HTMLElement | null} */ (null))

// La pestaña fija recuerda el último listado visitado (sus filtros viven en la dirección).
const consultaDelListado = ref({})
watch(
  () => ruta.name === 'incidentes' && ruta.query,
  (consulta) => consulta && (consultaDelListado.value = consulta),
  { immediate: true },
)
const enListado = computed(() => ruta.name === 'incidentes')

/** @param {import('@/modulos/incidentes/index.js').Pestana} pestana */
const destinoDe = (pestana) => ({ name: 'incidente', params: { id: pestana.id } })

/**
 * @param {number} id
 * @returns {Promise<void>}
 */
async function cerrar(id) {
  const eraActiva = pestanas.activa === id
  const siguiente = pestanas.cerrar(id)
  if (eraActiva) {
    const destino = siguiente === null ? null : pestanas.buscar(siguiente)
    await enrutador.push(destino ? destinoDe(destino) : { name: 'incidentes', query: consultaDelListado.value })
  }
}

/**
 * Flechas, Inicio y Fin recorren las pestañas; Supr cierra la enfocada.
 * @param {KeyboardEvent} evento
 * @returns {void}
 */
function alPulsarTecla(evento) {
  const elementos = /** @type {HTMLElement[]} */ ([...(barra.value?.querySelectorAll('[role="tab"]') ?? [])])
  const actual = elementos.indexOf(/** @type {HTMLElement} */ (document.activeElement))
  if (actual === -1) {
    return
  }
  const destinos = { ArrowRight: actual + 1, ArrowLeft: actual - 1, Home: 0, End: elementos.length - 1 }
  if (evento.key in destinos) {
    evento.preventDefault()
    const indice = (destinos[/** @type {keyof typeof destinos} */ (evento.key)] + elementos.length) % elementos.length
    elementos[indice]?.focus()
    return
  }
  const id = Number(elementos[actual]?.dataset.id)
  if (evento.key === 'Delete' && id) {
    evento.preventDefault()
    cerrar(id).then(() => nextTick(() => elementos[Math.min(actual, elementos.length - 2)]?.focus()))
  }
}
</script>

<template>
  <div
    v-if="pestanas.lista.length"
    ref="barra"
    class="barra-pestanas"
    role="tablist"
    aria-label="Incidentes abiertos"
    @keydown="alPulsarTecla"
  >
    <RouterLink
      :to="{ name: 'incidentes', query: consultaDelListado }"
      class="pt pt--fija"
      :class="{ 'pt--activa': enListado }"
      role="tab"
      :aria-selected="enListado"
      :tabindex="enListado || pestanas.activa === null ? 0 : -1"
    >
      <v-icon :icon="iconos.lista" size="16" />Incidentes
    </RouterLink>
    <TransitionGroup name="pt">
      <RouterLink
        v-for="pestana in pestanas.lista"
        :key="pestana.id"
        :to="destinoDe(pestana)"
        class="pt"
        :class="{ 'pt--activa': pestanas.activa === pestana.id }"
        role="tab"
        :data-id="pestana.id"
        :aria-selected="pestanas.activa === pestana.id"
        :tabindex="pestanas.activa === pestana.id ? 0 : -1"
        :title="pestana.titulo"
        aria-keyshortcuts="Delete"
        @auxclick.middle.prevent="cerrar(pestana.id)"
      >
        <span
          class="pt__punto"
          :style="{ background: pestana.estado ? presentarEstado(pestana.estado).color : 'var(--borde-fuerte)' }"
          aria-hidden="true"
        />
        <span class="pt__codigo">{{ pestana.codigo ?? `#${pestana.id}` }}</span>
        <span class="pt__titulo">{{ pestana.titulo }}</span>
        <!-- La × es para el puntero; con teclado se cierra con Supr (aria-keyshortcuts). Un botón dentro del
             enlace sería un control interactivo anidado. -->
        <span class="pt__cerrar" aria-hidden="true" @click.prevent.stop="cerrar(pestana.id)">
          <v-icon :icon="iconos.cerrar" size="14" />
        </span>
      </RouterLink>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.barra-pestanas {
  position: sticky;
  top: 56px;
  z-index: 5;
  display: flex;
  gap: 2px;
  align-items: flex-end;
  padding: 6px 16px 0;
  overflow: hidden;
  background: var(--fondo);
  border-bottom: 1px solid var(--borde);
}

.pt {
  position: relative;
  display: flex;
  flex: 1 1 220px;
  gap: 7px;
  align-items: center;
  min-width: 0;
  max-width: 240px;
  height: 36px;
  padding: 0 6px 0 12px;
  color: var(--suave);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  border: 1px solid transparent;
  border-bottom: 0;
  border-radius: var(--radio) var(--radio) 0 0;
  transition:
    background-color var(--rapida),
    color var(--rapida);
  container-type: inline-size;
}

.pt:hover {
  color: var(--texto);
  background: var(--resalte);
}

.pt--activa,
.pt--activa:hover {
  height: 37px;
  min-width: 142px;
  margin-bottom: -1px;
  color: var(--texto);
  background: var(--superficie);
  border-color: var(--borde);
}

.pt--activa::before {
  position: absolute;
  top: -1px;
  right: -1px;
  left: -1px;
  height: 2px;
  background: var(--acento);
  border-radius: var(--radio) var(--radio) 0 0;
  content: '';
}

/* Separador entre inactivas, que desaparece junto a la activa o la señalada. */
.pt:not(.pt--activa, .pt--fija)::after {
  position: absolute;
  top: 10px;
  bottom: 10px;
  left: 0;
  width: 1px;
  background: var(--borde-fuerte);
  content: '';
}

.pt--activa + .pt::after,
.pt:hover::after,
.pt:hover + .pt::after {
  opacity: 0;
}

.pt--fija {
  flex: none;
  max-width: none;
  padding-right: 12px;
  font-weight: 600;
  container-type: normal;
}

.pt__punto {
  flex: none;
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.pt__codigo {
  flex: none;
  color: var(--acento-texto);
  font-size: 12.5px;
  font-weight: 700;
  white-space: nowrap;
}

.pt__titulo {
  flex: 1 1 auto;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.pt__cerrar {
  display: grid;
  flex: none;
  place-items: center;
  width: 22px;
  height: 22px;
  color: var(--suave);
  background: transparent;
  border: 0;
  border-radius: var(--radio-chip);
  cursor: pointer;
  opacity: 0;
  transition:
    opacity var(--rapida),
    background-color var(--rapida);
}

.pt:hover .pt__cerrar,
.pt--activa .pt__cerrar,
.pt:focus-visible .pt__cerrar {
  opacity: 1;
}

.pt__cerrar:hover {
  color: var(--texto);
  background: var(--borde);
}

/* Como en el navegador: al estrecharse se oculta primero el título y luego la × de las inactivas; el
   código nunca se recorta y nunca hay desplazamiento. */
@container (width <= 175px) {
  .pt__titulo {
    display: none;
  }
}

@container (width <= 121px) {
  .pt:not(.pt--activa) .pt__cerrar {
    display: none;
  }
}

.pt-enter-active {
  transition:
    opacity 0.3s var(--curva),
    transform 0.3s var(--curva);
}

.pt-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.pt-leave-active {
  transition: opacity var(--rapida);
}

.pt-leave-to {
  opacity: 0;
}

.pt-move {
  transition: transform 0.3s var(--curva);
}
</style>
