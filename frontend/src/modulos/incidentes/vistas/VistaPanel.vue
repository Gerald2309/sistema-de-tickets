<script setup>
import { storeToRefs } from 'pinia'
import { computed, onBeforeUnmount, ref } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { haceCuanto } from '@/compartido/presentacion/fechas.js'
import { useSesion } from '@/modulos/identidad/index.js'
import AvisoMasivo from '../componentes/AvisoMasivo.vue'
import BarraPrioridades from '../componentes/BarraPrioridades.vue'
import FlujoEstados from '../componentes/FlujoEstados.vue'
import ListaVencenPronto from '../componentes/ListaVencenPronto.vue'
import MiTrabajo from '../componentes/MiTrabajo.vue'
import { usePanel } from '../composables/usePanel.js'

const { perfil } = storeToRefs(useSesion())
const { veTodo, resumen, avisos, vencenPronto, miTrabajo, actualizadoEn, actualizar } = usePanel()

// «Actualizado hace…» avanza solo.
const ahora = ref(Date.now())
const reloj = setInterval(() => (ahora.value = Date.now()), 5000)
onBeforeUnmount(() => clearInterval(reloj))

const accesos = computed(() => [
  ...(veTodo.value
    ? [
        {
          titulo: 'Asignados a mí',
          icono: iconos.asignadosAMi,
          cantidad: miTrabajo.asignados.data.value,
          filtros: miTrabajo.filtros.asignados,
        },
        {
          titulo: 'Asignados a mí que vencen hoy',
          icono: iconos.porVencer,
          cantidad: miTrabajo.vencenHoy.data.value,
          urgente: true,
          filtros: miTrabajo.filtros.vencenHoy,
        },
      ]
    : []),
  {
    titulo: 'Creados por mí',
    icono: iconos.creadosPorMi,
    cantidad: miTrabajo.creados.data.value,
    filtros: miTrabajo.filtros.creados,
  },
])
</script>

<template>
  <div class="pagina">
    <header class="cabecera">
      <div>
        <div class="migas entra">Mesa de ayuda · Panel</div>
        <h1 v-revelar class="titulo-pagina">Operación de hoy</h1>
        <p class="subtitulo entra" style="--i: 2">
          <span class="vivo" aria-hidden="true" />{{ perfil?.nombre }} · {{ perfil?.rol.nombre }}
          <template v-if="actualizadoEn"> · actualizado {{ haceCuanto(actualizadoEn, ahora) }}</template>
        </p>
      </div>
      <v-btn variant="outlined" class="entra" style="--i: 2" :prepend-icon="iconos.actualizar" @click="actualizar"
        >Actualizar</v-btn
      >
    </header>

    <TransitionGroup v-if="avisos.data.value?.length" name="texto" tag="div" class="panel__avisos">
      <AvisoMasivo v-for="aviso in avisos.data.value" :key="aviso.categoria" :aviso="aviso" />
    </TransitionGroup>

    <MensajeEnPagina
      v-if="resumen.isError.value"
      tipo="error"
      titulo="No se pudo cargar el resumen"
      reintentable
      class="mb-4"
      @reintentar="resumen.refetch()"
    >
      {{ resumen.error.value?.message }}
    </MensajeEnPagina>
    <div v-else-if="!resumen.data.value" class="tarjeta panel__cargando" aria-busy="true">
      <EsqueletoDeCarga v-for="n in 4" :key="n" alto="72px" />
    </div>
    <FlujoEstados v-else :resumen="resumen.data.value" class="entra" style="--i: 3" />

    <div class="panel__rejilla">
      <ListaVencenPronto
        :pagina="vencenPronto.data.value"
        :cargando="vencenPronto.isPending.value"
        class="entra"
        style="--i: 7"
      />
      <div class="panel__lateral">
        <MiTrabajo :accesos="accesos" class="entra" style="--i: 8" />
        <BarraPrioridades
          v-if="resumen.data.value"
          :por-prioridad="resumen.data.value.porPrioridad"
          :total="resumen.data.value.total"
          class="entra"
          style="--i: 9"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.panel__avisos {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.panel__cargando {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 16px;
}

.panel__rejilla {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 12px;
  margin-top: 12px;
  align-items: start;
}

.panel__lateral {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vivo {
  display: inline-block;
  width: 7px;
  height: 7px;
  margin-right: 7px;
  background: var(--marca-verde);
  border-radius: 50%;
  animation: pulso 2s infinite;
}

@media (width <= 960px) {
  .panel__rejilla {
    grid-template-columns: 1fr;
  }
}
</style>
