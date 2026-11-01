<script setup>
import { storeToRefs } from 'pinia'
import { computed, onBeforeUnmount, ref } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { haceCuanto } from '@/compartido/presentacion/fechas.js'
import { tiempoReal } from '@/compartido/tiempo-real.js'
import { useSesion } from '@/modulos/identidad/index.js'
import AvisoMasivo from '../componentes/AvisoMasivo.vue'
import CriticosVencidos from '../componentes/CriticosVencidos.vue'
import EsperaDePendientes from '../componentes/EsperaDePendientes.vue'
import FlujoEstados from '../componentes/FlujoEstados.vue'
import MiTrabajo from '../componentes/MiTrabajo.vue'
import TendenciaDeEntradas from '../componentes/TendenciaDeEntradas.vue'
import TiempoDeResolucion from '../componentes/TiempoDeResolucion.vue'
import { usePanel } from '../composables/usePanel.js'

const { perfil } = storeToRefs(useSesion())
const { veTodo, resumen, avisos, indicadores, criticosVencidos, miTrabajo, actualizadoEn, actualizar } = usePanel()

// «Actualizado hace…» avanza solo; con el socket conectado dice «en vivo», aunque nada haya cambiado hace rato.
const ahora = ref(Date.now())
const reloj = setInterval(() => (ahora.value = Date.now()), 5000)
onBeforeUnmount(() => clearInterval(reloj))

const estadoDeLosDatos = computed(() => {
  if (tiempoReal.conectado) {
    return 'en vivo'
  }
  return actualizadoEn.value
    ? `sin conexión en vivo · actualizado ${haceCuanto(actualizadoEn.value, ahora.value)}`
    : 'sin conexión en vivo'
})

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
          <span class="vivo" :class="{ 'vivo--conectado': tiempoReal.conectado }" aria-hidden="true" />{{
            perfil?.nombre
          }}
          · {{ perfil?.rol.nombre }} · {{ estadoDeLosDatos }}
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

    <MensajeEnPagina
      v-if="indicadores.isError.value"
      tipo="error"
      titulo="No se pudieron cargar los indicadores"
      reintentable
      class="mt-3"
      @reintentar="indicadores.refetch()"
    >
      {{ indicadores.error.value?.message }}
    </MensajeEnPagina>

    <div class="panel__rejilla">
      <div class="panel__columna">
        <EsperaDePendientes :espera="indicadores.data.value?.espera" class="entra" style="--i: 7" />
        <CriticosVencidos
          :pagina="criticosVencidos.data.value"
          :cargando="criticosVencidos.isPending.value"
          class="entra"
          style="--i: 8"
        />
      </div>
      <div class="panel__columna">
        <MiTrabajo :accesos="accesos" class="entra" style="--i: 8" />
        <TiempoDeResolucion :resolucion="indicadores.data.value?.resolucion" class="entra" style="--i: 9" />
      </div>
    </div>
    <TendenciaDeEntradas :tendencia="indicadores.data.value?.tendencia" class="panel__ancho entra" style="--i: 10" />
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
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  gap: 12px;
  margin-top: 12px;
}

/* Las dos columnas terminan a la misma altura: la última tarjeta de cada una ocupa lo que sobra. */
.panel__columna {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

.panel__columna > :last-child {
  flex: 1;
}

.panel__ancho {
  margin-top: 12px;
}

.vivo {
  display: inline-block;
  width: 7px;
  height: 7px;
  margin-right: 7px;
  background: var(--estado-cerrado);
  border-radius: 50%;
}

.vivo--conectado {
  background: var(--marca-verde);
  animation: pulso 2s infinite;
}

@media (width <= 960px) {
  .panel__rejilla {
    grid-template-columns: 1fr;
  }
}
</style>
