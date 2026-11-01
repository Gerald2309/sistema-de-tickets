<script setup>
import { computed, ref, watch } from 'vue'
import ListaPropiedades from '@/compartido/componentes/ListaPropiedades.vue'
import SelectorFechaHora from '@/compartido/componentes/SelectorFechaHora.vue'
import { iconos } from '@/compartido/iconos.js'
import { aIsoConDesfase, fechaHora } from '@/compartido/presentacion/fechas.js'
import { CATEGORIAS, PRIORIDADES, nombreDeCategoria } from '../presentacion.js'
import IndicadorDeVencimiento from './IndicadorDeVencimiento.vue'
import InsigniaPrioridad from './InsigniaPrioridad.vue'
import TemporizadorDePlazos from './TemporizadorDePlazos.vue'

/**
 * Propiedades del incidente (RF-F28). Quien lo atiende lo clasifica aquí mismo; el título y la descripción
 * quedan fijos al crearlo. Cada cambio se envía solo, con `ajustar`. Quien lo reportó ve el plazo como la
 * fecha estimada de respuesta, sin la alarma de vencido, que es para soporte.
 */
const props = defineProps({
  incidente: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente>} */ (Object), required: true },
  partes: {
    type: /** @type {import('vue').PropType<import('../permisos.js').PartesEditables>} */ (Object),
    required: true,
  },
  pendiente: { type: Boolean, default: false },
  vistaDelSolicitante: { type: Boolean, default: false },
})
const emit = defineEmits(['ajustar'])

const fijandoVencimiento = ref(false)
const vencimiento = ref(/** @type {Date | null} */ (null))
const sinResolver = computed(() => props.incidente.estado === 'abierto' || props.incidente.estado === 'en_progreso')

const propiedades = computed(() => [
  { etiqueta: 'Prioridad' },
  { etiqueta: 'Categoría' },
  { etiqueta: 'Solicitante', valor: props.incidente.creador.nombre },
  { etiqueta: 'Empresa', valor: props.incidente.cliente?.nombre ?? 'Sin empresa', tenue: !props.incidente.cliente },
  { etiqueta: props.vistaDelSolicitante ? 'Respuesta estimada' : 'Vence' },
  { etiqueta: 'Actualizado', valor: fechaHora(props.incidente.actualizadoEn) },
  ...(props.incidente.calificacion === null
    ? []
    : [{ etiqueta: 'Calificación', valor: `${props.incidente.calificacion} de 5` }]),
])

// Al abrir, el selector parte del vencimiento actual.
watch(fijandoVencimiento, (abierto) => {
  if (abierto) {
    vencimiento.value = new Date(props.incidente.fechaVencimiento)
  }
})

function guardarVencimiento() {
  fijandoVencimiento.value = false
  if (vencimiento.value) {
    emit('ajustar', { fechaVencimiento: aIsoConDesfase(vencimiento.value) })
  }
}
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-propiedades">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-propiedades" class="tarjeta__titulo">Propiedades</h2>
    </header>
    <div class="tarjeta__cuerpo">
      <ListaPropiedades :elementos="propiedades">
        <template #valor-0>
          <v-menu v-if="partes.clasificacion">
            <template #activator="{ props: activador }">
              <button v-bind="activador" type="button" class="filtro valor-editable" :disabled="pendiente">
                <InsigniaPrioridad :prioridad="incidente.prioridad" />
                <span class="d-sr-only">: cambiar prioridad</span>
                <v-icon :icon="iconos.desplegar" size="16" />
              </button>
            </template>
            <v-list min-width="180">
              <v-list-item
                v-for="(prioridad, valor) in PRIORIDADES"
                :key="valor"
                :title="prioridad.nombre"
                :active="valor === incidente.prioridad"
                @click="valor !== incidente.prioridad && emit('ajustar', { prioridad: valor })"
              >
                <template #prepend
                  ><v-icon :icon="prioridad.icono" size="16" :style="{ color: prioridad.color }" class="mr-3"
                /></template>
              </v-list-item>
            </v-list>
          </v-menu>
          <InsigniaPrioridad v-else :prioridad="incidente.prioridad" />
        </template>

        <template #valor-1>
          <v-menu v-if="partes.clasificacion">
            <template #activator="{ props: activador }">
              <button v-bind="activador" type="button" class="filtro valor-editable" :disabled="pendiente">
                {{ nombreDeCategoria(incidente.categoria) }}
                <span class="d-sr-only">: cambiar categoría</span>
                <v-icon :icon="iconos.desplegar" size="16" />
              </button>
            </template>
            <v-list min-width="200">
              <v-list-item
                v-for="(nombre, valor) in CATEGORIAS"
                :key="valor"
                :title="nombre"
                :active="valor === incidente.categoria"
                @click="valor !== incidente.categoria && emit('ajustar', { categoria: valor })"
              />
            </v-list>
          </v-menu>
          <template v-else>{{ nombreDeCategoria(incidente.categoria) }}</template>
        </template>

        <template #valor-4>
          <v-menu v-if="partes.clasificacion" v-model="fijandoVencimiento" :close-on-content-click="false">
            <template #activator="{ props: activador }">
              <button v-bind="activador" type="button" class="filtro valor-editable" :disabled="pendiente">
                <template v-if="vistaDelSolicitante">{{ fechaHora(incidente.fechaVencimiento) }}</template>
                <IndicadorDeVencimiento
                  v-else
                  :fecha="incidente.fechaVencimiento"
                  :vencido="incidente.vencido"
                  :pendiente="sinResolver"
                />
                <span class="d-sr-only">: cambiar vencimiento</span>
                <v-icon :icon="iconos.desplegar" size="16" />
              </button>
            </template>
            <v-card class="menu-vencimiento" min-width="300">
              <form @submit.prevent="guardarVencimiento">
                <SelectorFechaHora v-model="vencimiento" etiqueta="Vence" />
                <div class="menu-vencimiento__acciones">
                  <v-btn variant="text" @click="fijandoVencimiento = false">Cancelar</v-btn>
                  <v-btn type="submit" color="primary" variant="flat" :disabled="!vencimiento">Guardar</v-btn>
                </div>
              </form>
            </v-card>
          </v-menu>
          <template v-else-if="vistaDelSolicitante">{{ fechaHora(incidente.fechaVencimiento) }}</template>
          <IndicadorDeVencimiento
            v-else
            :fecha="incidente.fechaVencimiento"
            :vencido="incidente.vencido"
            :pendiente="sinResolver"
          />
        </template>
      </ListaPropiedades>
      <TemporizadorDePlazos v-if="!vistaDelSolicitante" :incidente="incidente" />
    </div>
  </section>
</template>

<style scoped>
/* El valor sigue alineado a la derecha como los demás; el fondo gris al pasar indica que se puede cambiar. */
.valor-editable {
  height: 28px;
  margin: -4px -8px -4px 0;
  padding: 0 6px;
}

.menu-vencimiento {
  padding: 12px;
  border: 1px solid var(--borde);
}

.menu-vencimiento__acciones {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 8px;
}
</style>
