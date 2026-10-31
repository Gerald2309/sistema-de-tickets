<script setup>
import { computed, ref } from 'vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { useSesion } from '@/modulos/identidad/index.js'
import CarruselPorConfirmar from '../componentes/CarruselPorConfirmar.vue'
import ListaDeSolicitudes from '../componentes/ListaDeSolicitudes.vue'
import ResumenDeSolicitudes from '../componentes/ResumenDeSolicitudes.vue'
import { useConfirmacionDeSolucion } from '../composables/useConfirmacionDeSolucion.js'
import { useMisSolicitudes } from '../composables/useMisSolicitudes.js'

const sesion = useSesion()
const pestana = ref('en-curso')
const solicitudes = useMisSolicitudes(() => pestana.value === 'cerradas')
const { porConfirmar, enCurso } = solicitudes
// Las soluciones por confirmar se recorren de a una y se responden en un paso, sin salir del inicio.
const { confirmar, confirmandoA } = useConfirmacionDeSolucion()

const pestanas = computed(() => [
  { valor: 'en-curso', etiqueta: 'En curso', conteo: enCurso.value.length },
  { valor: 'cerradas', etiqueta: 'Cerradas' },
])
const lista = computed(() =>
  pestana.value === 'cerradas'
    ? { elementos: solicitudes.cerradas.value, vacio: 'Aún no tiene solicitudes cerradas.' }
    : { elementos: enCurso.value, vacio: 'No tiene solicitudes en curso.' },
)
</script>

<template>
  <div class="pagina">
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Mis solicitudes</h1>
        <p class="subtitulo entra" style="--i: 2">Lo que reportó y en qué va cada cosa.</p>
      </div>
      <v-btn
        v-if="sesion.tiene('crear_incidente')"
        color="primary"
        variant="flat"
        :prepend-icon="iconos.crear"
        :to="{ name: 'nuevo-incidente' }"
        class="entra"
        style="--i: 2"
        >Reportar un problema</v-btn
      >
    </header>

    <MensajeEnPagina
      v-if="solicitudes.error.value"
      tipo="error"
      titulo="No se pudieron cargar sus solicitudes"
      reintentable
      @reintentar="solicitudes.reintentar"
    >
      {{ solicitudes.error.value.message }}
    </MensajeEnPagina>

    <div v-else-if="solicitudes.cargando.value" class="mis-solicitudes" aria-busy="true">
      <EsqueletoDeCarga alto="180px" />
      <EsqueletoDeCarga alto="240px" />
    </div>

    <div v-else class="mis-solicitudes">
      <CarruselPorConfirmar
        :elementos="porConfirmar"
        :pendiente-de="confirmandoA"
        class="entra"
        style="--i: 3"
        @confirmar="confirmar"
      />

      <ResumenDeSolicitudes
        :totales="solicitudes.totales.value"
        :con-respuesta="solicitudes.conRespuesta.value"
        class="entra"
        style="--i: 4"
      />

      <section class="tarjeta entra" style="--i: 5" aria-label="Sus solicitudes">
        <header class="tarjeta__cabecera">
          <ControlSegmentado v-model="pestana" :opciones="pestanas" etiqueta="Qué solicitudes ver" />
        </header>
        <div
          v-if="pestana === 'cerradas' && solicitudes.cargandoCerradas.value"
          class="tarjeta__cuerpo"
          aria-busy="true"
        >
          <EsqueletoDeCarga alto="96px" />
        </div>
        <ListaDeSolicitudes v-else :elementos="lista.elementos" :vacio="lista.vacio" />
        <RouterLink :to="{ name: 'incidentes' }" class="mis-solicitudes__todas">
          {{ solicitudes.recortada.value ? 'Hay más: ver todas en el listado' : 'Buscar en todas mis solicitudes' }} →
        </RouterLink>
      </section>
    </div>
  </div>
</template>

<style scoped>
.mis-solicitudes {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.mis-solicitudes__todas {
  display: block;
  padding: 10px 16px;
  color: var(--acento);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  border-top: 1px solid var(--borde);
}

.mis-solicitudes__todas:hover {
  text-decoration: underline;
}
</style>
