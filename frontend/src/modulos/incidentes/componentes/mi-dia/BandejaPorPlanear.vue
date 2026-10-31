<script setup>
import { computed, ref } from 'vue'
import CampoDeBusqueda from '@/compartido/componentes/CampoDeBusqueda.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { iconos } from '@/compartido/iconos.js'
import { agruparPorUrgencia, empezarArrastre, leerArrastre } from '../../mi-dia.js'
import CodigoTicket from '../CodigoTicket.vue'
import IndicadorDeVencimiento from '../IndicadorDeVencimiento.vue'
import InsigniaPrioridad from '../InsigniaPrioridad.vue'
import MenuPlanear from './MenuPlanear.vue'

/**
 * Lo asignado que aún no tiene día, agrupado por urgencia y con alto fijo: llega por tramos y se busca, no
 * crece sin control. Se arrastra a la agenda o se usa «Planear ▾». Soltar aquí algo de la agenda lo devuelve.
 */
const props = defineProps({
  incidentes: {
    type: /** @type {import('vue').PropType<import('../../api/mi-dia.js').IncidenteDelPlan[]>} */ (Array),
    required: true,
  },
  ahora: { type: Date, required: true },
  hoy: { type: String, required: true },
  cargando: { type: Boolean, default: false },
  hayMas: { type: Boolean, default: false },
  cargandoMas: { type: Boolean, default: false },
})
const buscar = defineModel('buscar', { type: String, default: '' })
const emit = defineEmits(['planear', 'devolver', 'ver-mas'])

const raiz = ref(/** @type {HTMLElement | null} */ (null))
const encima = ref(false)
const grupos = computed(() => agruparPorUrgencia(props.incidentes, props.ahora))

/** @param {DragEvent} evento */
function alSoltar(evento) {
  encima.value = false
  const arrastrado = leerArrastre(evento)
  if (arrastrado?.tipo === 'entrada') {
    emit('devolver', arrastrado.id)
  }
}

defineExpose({ enfocar: () => raiz.value?.querySelector('input')?.focus() })
</script>

<template>
  <section
    ref="raiz"
    class="tarjeta bandeja"
    :class="{ 'bandeja--encima': encima }"
    aria-labelledby="titulo-por-planear"
  >
    <header class="tarjeta__cabecera">
      <h2 id="titulo-por-planear" class="tarjeta__titulo">Por planear</h2>
      <span class="nota atajos"><kbd>/</kbd> buscar · <kbd>N</kbd> nota</span>
    </header>
    <div class="bandeja__buscador">
      <CampoDeBusqueda v-model="buscar" etiqueta="Buscar por código o título" />
    </div>
    <div
      class="bandeja__lista"
      tabindex="0"
      aria-label="Incidentes por planear"
      @dragover.prevent="encima = true"
      @dragleave="encima = false"
      @drop.prevent="alSoltar"
    >
      <div v-if="cargando" class="bandeja__esqueleto" aria-busy="true">
        <EsqueletoDeCarga v-for="n in 4" :key="n" alto="34px" />
      </div>
      <p v-else-if="grupos.length === 0" class="bandeja__vacio">
        {{ buscar ? 'Ningún incidente coincide.' : 'Todo lo asignado ya tiene día.' }}
      </p>
      <template v-for="grupo in grupos" v-else :key="grupo.clave">
        <div class="bandeja__grupo" :class="{ 'bandeja__grupo--urgente': grupo.clave === 'hoy' }">
          {{ grupo.nombre }} · {{ grupo.elementos.length }}
        </div>
        <div
          v-for="incidente in grupo.elementos"
          :key="incidente.id"
          class="bandeja__fila"
          draggable="true"
          @dragstart="empezarArrastre($event, { tipo: 'incidente', id: incidente.id })"
        >
          <v-icon :icon="iconos.arrastrar" size="16" class="bandeja__asa" aria-hidden="true" />
          <div class="bandeja__cuerpo">
            <router-link :to="{ name: 'incidente', params: { id: incidente.id } }" class="bandeja__linea enlace-sutil">
              <CodigoTicket :codigo="incidente.codigo" />
              <span class="bandeja__titulo">{{ incidente.titulo }}</span>
            </router-link>
            <div class="bandeja__linea">
              <span class="bandeja__datos">
                <InsigniaPrioridad :prioridad="incidente.prioridad" />
                <IndicadorDeVencimiento
                  :fecha="incidente.fechaVencimiento"
                  :vencido="incidente.vencido"
                  :ahora="ahora"
                />
              </span>
              <MenuPlanear :hoy="hoy" @elegir="emit('planear', { tipo: 'incidente', id: incidente.id }, $event)" />
            </div>
            <span v-if="incidente.bloqueo" class="bloqueo" :title="incidente.bloqueo">
              <v-icon :icon="iconos.bloqueado" size="14" aria-hidden="true" />Bloqueado: {{ incidente.bloqueo }}
            </span>
          </div>
        </div>
      </template>
    </div>
    <footer v-if="hayMas" class="bandeja__pie">
      <v-btn variant="text" size="small" color="primary" :loading="cargandoMas" @click="emit('ver-mas')">Ver más</v-btn>
    </footer>
  </section>
</template>

<style scoped>
.bandeja {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.bandeja--encima {
  border-color: var(--acento);
}

.bandeja__buscador {
  padding: 10px 12px;
}

.bandeja__lista {
  max-height: 420px;
  overflow: hidden auto;
  border-top: 1px solid var(--borde);
}

.bandeja__lista:focus-visible {
  outline: none;
  box-shadow: inset var(--anillo-foco);
}

.bandeja__grupo {
  position: sticky;
  top: 0;
  z-index: 1;
  padding: 5px 12px;
  color: var(--suave);
  font-size: 11.5px;
  font-weight: 600;
  background: var(--sutil);
  border-bottom: 1px solid var(--borde);
}

.bandeja__grupo--urgente {
  color: var(--error);
}

.bandeja__fila {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  min-height: 40px;
  padding: 8px 12px;
  border-bottom: 1px solid var(--borde);
  cursor: grab;
}

.bandeja__fila:hover .bandeja__asa {
  color: var(--suave);
}

.bandeja__asa {
  flex: none;
  margin-top: 2px;
  color: var(--borde-fuerte);
}

.bandeja__cuerpo {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.bandeja__linea {
  display: flex;
  gap: 10px;
  align-items: center;
  min-width: 0;
}

.bandeja__titulo {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.bandeja__datos {
  display: flex;
  flex: 1;
  gap: 10px;
  align-items: center;
  min-width: 0;
  overflow: hidden;
  font-size: 13px;
}

.bandeja__esqueleto {
  display: grid;
  gap: 10px;
  padding: 12px;
}

.bandeja__vacio {
  padding: 22px 16px;
  color: var(--suave);
  text-align: center;
}

.bandeja__pie {
  padding: 4px 8px;
  text-align: center;
  border-top: 1px solid var(--borde);
}

.bloqueo {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  min-width: 0;
  overflow: hidden;
  color: var(--advertencia);
  font-size: 12.5px;
  font-weight: 500;
  white-space: nowrap;
  text-overflow: ellipsis;
}

kbd {
  padding: 0 5px;
  color: var(--suave);
  font: 600 11px/18px var(--fuente);
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: 3px;
}

.atajos {
  white-space: nowrap;
}
</style>
