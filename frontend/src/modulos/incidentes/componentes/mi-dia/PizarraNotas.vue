<script setup>
import { ref } from 'vue'
import { colorDeNota, empezarArrastre, leerArrastre } from '../../mi-dia.js'
import CodigoTicket from '../CodigoTicket.vue'
import MenuPlanear from './MenuPlanear.vue'

/**
 * La pizarra de notas: papelitos en tonos suaves de la marca, más cercanos que una tabla. Enter guarda la
 * nota y deja el campo listo para la siguiente; se arrastran a una hora o se les da hora con el menú.
 */
defineProps({
  notas: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').NotaPersonal[]>} */ (Array),
    required: true,
  },
  hoy: { type: String, required: true },
})
const emit = defineEmits(['crear', 'marcar', 'borrar', 'planear', 'devolver'])

const MAXIMO = 200
const texto = ref('')
const campo = ref(/** @type {HTMLTextAreaElement | null} */ (null))

/** @param {KeyboardEvent} evento */
function alPulsarTecla(evento) {
  if (evento.key !== 'Enter' || evento.shiftKey) {
    return
  }
  evento.preventDefault()
  const nota = texto.value.trim()
  if (nota) {
    emit('crear', nota)
    texto.value = ''
  }
}

/** @param {DragEvent} evento */
function alSoltar(evento) {
  const arrastrado = leerArrastre(evento)
  if (arrastrado?.tipo === 'entrada') {
    emit('devolver', arrastrado.id)
  }
}

defineExpose({ enfocar: () => campo.value?.focus() })
</script>

<template>
  <section class="pizarra" aria-labelledby="titulo-notas" @dragover.prevent @drop.prevent="alSoltar">
    <header class="pizarra__cabecera">
      <h2 id="titulo-notas" class="pizarra__titulo">Mis notas</h2>
      <span class="nota">Solo usted las ve · arrástrelas a una hora</span>
    </header>
    <div class="pizarra__notas">
      <label class="papel papel--nuevo">
        <textarea
          ref="campo"
          v-model="texto"
          rows="3"
          :maxlength="MAXIMO"
          placeholder="+ Nota: llamar a…, enviar mensaje a…"
          aria-label="Nueva nota (Enter para guardar)"
          @keydown="alPulsarTecla"
        />
      </label>
      <TransitionGroup name="papel">
        <article
          v-for="nota in notas"
          :key="nota.id"
          class="papel"
          :class="[`papel--${colorDeNota(nota.id)}`, { 'papel--hecho': nota.hecha }]"
          :draggable="!nota.hecha"
          @dragstart="empezarArrastre($event, { tipo: 'nota', id: nota.id })"
        >
          <input
            type="checkbox"
            class="papel__marca"
            :checked="nota.hecha"
            :aria-label="`Hecha: ${nota.texto}`"
            @change="emit('marcar', nota, /** @type {HTMLInputElement} */ ($event.target).checked)"
          />
          <p class="papel__texto">{{ nota.texto }}</p>
          <footer class="papel__pie">
            <router-link
              v-if="nota.incidente"
              :to="{ name: 'incidente', params: { id: nota.incidente.id } }"
              class="enlace-sutil"
            >
              <CodigoTicket :codigo="nota.incidente.codigo" />
            </router-link>
            <span class="papel__acciones">
              <MenuPlanear
                v-if="!nota.hecha"
                :hoy="hoy"
                @elegir="emit('planear', { tipo: 'nota', id: nota.id }, $event)"
              >
                <template #activador="{ props: activador }">
                  <button v-bind="activador" type="button" class="papel__boton">Darle hora</button>
                </template>
              </MenuPlanear>
              <button type="button" class="papel__boton" aria-label="Borrar nota" @click="emit('borrar', nota)">
                ✕
              </button>
            </span>
          </footer>
        </article>
      </TransitionGroup>
    </div>
  </section>
</template>

<style scoped>
.pizarra {
  position: sticky;
  top: 72px;
  min-width: 0;
}

.pizarra__cabecera {
  display: flex;
  flex-direction: column;
  margin: 4px 2px 10px;
}

.pizarra__titulo {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
}

.pizarra__notas {
  display: grid;
  gap: 10px;
  max-height: calc(100vh - 140px);
  padding: 2px 2px 8px;
  overflow-y: auto;
}

.papel {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 96px;
  padding: 10px 10px 8px 34px;
  background: var(--tinte);
  border-radius: 10px;
  box-shadow: var(--sombra-1);
  cursor: grab;
  transition:
    transform 0.25s var(--curva),
    box-shadow 0.25s var(--curva),
    opacity var(--rapida);
}

.papel:hover {
  box-shadow: var(--sombra-2);
  transform: translateY(-2px) rotate(-0.5deg);
}

.papel--cian {
  --tinte: var(--papel-cian);
}

.papel--naranja {
  --tinte: var(--papel-naranja);
}

.papel--verde {
  --tinte: var(--papel-verde);
}

.papel__marca {
  position: absolute;
  top: 12px;
  left: 11px;
  display: grid;
  place-items: center;
  width: 16px;
  height: 16px;
  margin: 0;
  background: var(--superficie);
  border: 1.5px solid var(--borde-fuerte);
  border-radius: 50%;
  cursor: pointer;
  appearance: none;
}

.papel__marca:checked {
  background: var(--estado-resuelto);
  border-color: var(--estado-resuelto);
}

.papel__marca:checked::after {
  width: 4px;
  height: 8px;
  border: solid var(--superficie);
  border-width: 0 2px 2px 0;
  transform: translate(0, -1px) rotate(45deg);
  content: '';
}

.papel__marca:focus-visible {
  outline: none;
  box-shadow: var(--anillo-foco);
}

.papel__texto {
  margin: 0;
  font-size: 13.5px;
  font-weight: 500;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.papel__pie {
  display: flex;
  gap: 8px;
  align-items: center;
  min-height: 22px;
  margin-top: auto;
}

.papel__acciones {
  display: flex;
  gap: 2px;
  margin-left: auto;
  opacity: 0;
  transition: opacity var(--rapida);
}

.papel:hover .papel__acciones,
.papel:focus-within .papel__acciones {
  opacity: 1;
}

.papel__boton {
  height: 22px;
  padding: 0 6px;
  color: var(--suave);
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
}

.papel__boton:hover,
.papel__boton:focus-visible {
  background: var(--superficie);
  outline: none;
}

.papel--hecho {
  cursor: default;
  opacity: 0.55;
}

.papel--hecho .papel__texto {
  text-decoration: line-through;
}

.papel--nuevo {
  padding: 10px 12px;
  background: transparent;
  border: 1.5px dashed var(--borde-fuerte);
  box-shadow: none;
  cursor: text;
}

.papel--nuevo:hover {
  border-color: var(--acento);
  box-shadow: none;
  transform: none;
}

.papel--nuevo:focus-within {
  background: var(--superficie);
  border-color: var(--acento);
}

.papel--nuevo textarea {
  flex: 1;
  width: 100%;
  color: var(--texto);
  font: inherit;
  font-size: 13.5px;
  background: none;
  border: 0;
  outline: none;
  resize: none;
}

.papel-enter-active,
.papel-leave-active {
  transition:
    opacity var(--media) var(--curva),
    transform var(--media) var(--curva);
}

.papel-enter-from,
.papel-leave-to {
  opacity: 0;
  transform: scale(0.96);
}

@media (width <= 1200px) {
  .pizarra {
    position: static;
  }

  .pizarra__cabecera {
    flex-direction: row;
    gap: 12px;
    align-items: baseline;
    justify-content: space-between;
  }

  .pizarra__notas {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    max-height: none;
  }
}

@media (hover: none) {
  .papel__acciones {
    opacity: 1;
  }
}
</style>
