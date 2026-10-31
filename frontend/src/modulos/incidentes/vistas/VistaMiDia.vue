<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import DialogoTexto from '@/compartido/componentes/DialogoTexto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import CodigoTicket from '../componentes/CodigoTicket.vue'
import AgendaDelDia from '../componentes/mi-dia/AgendaDelDia.vue'
import BandejaPorPlanear from '../componentes/mi-dia/BandejaPorPlanear.vue'
import MesDelPlan from '../componentes/mi-dia/MesDelPlan.vue'
import PizarraNotas from '../componentes/mi-dia/PizarraNotas.vue'
import SemanaDelPlan from '../componentes/mi-dia/SemanaDelPlan.vue'
import TarjetaAhora from '../componentes/mi-dia/TarjetaAhora.vue'
import { useMiDia } from '../composables/useMiDia.js'
import {
  JORNADA,
  aFecha,
  ahoraYSiguiente,
  cuadriculaDelMes,
  estaTerminada,
  semanaLaboral,
  sumarDias,
} from '../mi-dia.js'

/** @typedef {import('../composables/useMiDia.js').EntradaDelPlan} EntradaDelPlan */
/** @typedef {import('../mi-dia.js').Arrastrado} Arrastrado */

/**
 * «Mi día» (soporte): su espacio personal para organizar el trabajo. A la izquierda lo por planear, al centro
 * la agenda (día por horas, semana o mes) y a la derecha la pizarra de notas. Nadie más lo ve.
 */
const miDia = useMiDia()
const { vista, dia, hoy } = miDia

const VISTAS = [
  { valor: 'dia', etiqueta: 'Día' },
  { valor: 'semana', etiqueta: 'Semana' },
  { valor: 'mes', etiqueta: 'Mes' },
]
const formatoDia = new Intl.DateTimeFormat('es', { weekday: 'short', day: 'numeric', month: 'short' })
const formatoMes = new Intl.DateTimeFormat('es', { month: 'long', year: 'numeric' })

const titulo = computed(() => {
  if (vista.value === 'mes') {
    const texto = formatoMes.format(aFecha(dia.value))
    return texto.charAt(0).toUpperCase() + texto.slice(1)
  }
  if (vista.value === 'semana') {
    const semana = semanaLaboral(dia.value)
    return `Semana del ${formatoDia.format(aFecha(semana[0]))} al ${formatoDia.format(aFecha(semana[4]))}`
  }
  const fecha = formatoDia.format(aFecha(dia.value))
  return dia.value === hoy.value ? `Hoy · ${fecha}` : fecha
})

const delDia = miDia.delDia
const esHoy = computed(() => vista.value === 'dia' && dia.value === hoy.value)
const enCurso = computed(() =>
  esHoy.value ? ahoraYSiguiente(delDia.value, miDia.minutoActual.value) : { ahora: null, siguiente: null },
)
const hechos = computed(() => delDia.value.filter(estaTerminada).length)
const bloqueados = computed(
  () => delDia.value.filter((entrada) => entrada.incidente?.bloqueo && !estaTerminada(entrada)).length,
)
const resumen = computed(() => {
  const partes = [`${hechos.value} de ${delDia.value.length} hechos`]
  if (bloqueados.value) {
    partes.push(`${bloqueados.value} bloqueado${bloqueados.value > 1 ? 's' : ''}`)
  }
  return partes.join(' · ')
})
// La barra: lo planeado en tono claro y lo hecho encima, frente a lo habitual (con algo de margen).
const escala = (/** @type {number} */ cantidad) => `scaleX(${Math.min(cantidad / (JORNADA.capacidadHabitual + 2), 1)})`
const casiVacio = computed(() => delDia.value.filter((entrada) => !estaTerminada(entrada)).length < 3)

const notasSueltas = computed(() => miDia.notas.data.value ?? [])
const pendientes = computed(() => (dia.value === hoy.value ? (miDia.pendientes.data.value ?? []) : []))
const opcionesRapidas = computed(() => [
  ...miDia.porPlanearLista.value.map((incidente) => ({
    tipo: /** @type {const} */ ('incidente'),
    id: incidente.id,
    codigo: incidente.codigo,
    texto: incidente.titulo,
  })),
  ...notasSueltas.value
    .filter((nota) => !nota.hecha)
    .map((nota) => ({ tipo: /** @type {const} */ ('nota'), id: nota.id, texto: nota.texto })),
])

/** @param {number} id */
const entradaDe = (id) => miDia.entradas.value.find((entrada) => entrada.id === id)

/**
 * Soltar en un día y una hora: lo de la agenda se mueve, lo de la bandeja o la pizarra se planea.
 * @param {Arrastrado} arrastrado
 * @param {string} fecha
 * @param {number} [inicio] en la semana, se conserva la hora de la entrada o se busca desde el inicio de la jornada
 */
function soltar(arrastrado, fecha, inicio) {
  if (arrastrado.tipo !== 'entrada') {
    void miDia.planear({ tipo: arrastrado.tipo, id: arrastrado.id }, fecha, inicio ?? JORNADA.inicio)
    return
  }
  const entrada = entradaDe(arrastrado.id)
  if (entrada) {
    void miDia.reubicar(entrada, { fecha, inicio: inicio ?? entrada.inicio })
  }
}

// Lo que llega de la agenda del día a la vista.
/**
 * @param {Arrastrado} arrastrado
 * @param {number} inicio
 */
const alSoltarEnElDia = (arrastrado, inicio) => soltar(arrastrado, dia.value, inicio)
/**
 * @param {EntradaDelPlan} entrada
 * @param {number} duracion
 */
const alCambiarDuracion = (entrada, duracion) => miDia.reubicar(entrada, { duracion })
/** @param {EntradaDelPlan} entrada */
const pasarAManana = (entrada) =>
  miDia.reubicar(entrada, { fecha: sumarDias(entrada.fecha, 1), inicio: JORNADA.inicio })
/** @param {EntradaDelPlan} entrada */
const alDesbloquear = (entrada) => entrada.incidente && miDia.marcarBloqueo(entrada.incidente, null)
/**
 * @param {import('../composables/useMiDia.js').Planeable} que
 * @param {number} inicio
 */
const alElegirEnHueco = (que, inicio) => miDia.planear(que, dia.value, inicio)

/** @param {number} id */
function devolver(id) {
  const entrada = entradaDe(id)
  if (entrada) {
    void miDia.quitar(entrada)
  }
}

// La marca de bloqueado pide su motivo.
const bloqueando = ref(/** @type {import('../composables/useMiDia.js').IncidenteDelPlan | null} */ (null))
const dialogoDeBloqueo = computed({
  get: () => bloqueando.value !== null,
  set: (abierto) => !abierto && (bloqueando.value = null),
})
/** @param {EntradaDelPlan} entrada */
const alBloquear = (entrada) => (bloqueando.value = entrada.incidente)
/** @param {string} motivo */
async function confirmarBloqueo(motivo) {
  if (bloqueando.value) {
    await miDia.marcarBloqueo(bloqueando.value, motivo)
  }
  bloqueando.value = null
}

// Atajos: «/» busca en la bandeja y «N» escribe una nota, salvo mientras se escribe en un campo.
const bandeja = ref(/** @type {InstanceType<typeof BandejaPorPlanear> | null} */ (null))
const pizarra = ref(/** @type {InstanceType<typeof PizarraNotas> | null} */ (null))
/** @param {KeyboardEvent} evento */
function alPulsarTecla(evento) {
  const objetivo = /** @type {HTMLElement} */ (evento.target)
  if (
    evento.ctrlKey ||
    evento.metaKey ||
    evento.altKey ||
    objetivo.closest('input, textarea, [contenteditable="true"]')
  ) {
    return
  }
  if (evento.key === '/') {
    evento.preventDefault()
    bandeja.value?.enfocar()
  } else if (evento.key.toLowerCase() === 'n') {
    evento.preventDefault()
    pizarra.value?.enfocar()
  }
}
onMounted(() => window.addEventListener('keydown', alPulsarTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', alPulsarTecla))
</script>

<template>
  <div class="pagina pagina--mi-dia">
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Mi día</h1>
        <p class="subtitulo entra" style="--i: 2">Su espacio para organizar el trabajo. Solo usted lo ve.</p>
      </div>
      <ControlSegmentado v-model="vista" :opciones="VISTAS" etiqueta="Vista del plan" />
    </header>

    <TarjetaAhora :ahora="enCurso.ahora" :siguiente="enCurso.siguiente" :ultimo="miDia.ultimo.data.value ?? null" />

    <div class="mi-dia">
      <BandejaPorPlanear
        ref="bandeja"
        v-model:buscar="miDia.buscar.value"
        :incidentes="miDia.porPlanearLista.value"
        :ahora="miDia.ahora.value"
        :hoy="hoy"
        :cargando="miDia.porPlanear.isPending.value"
        :hay-mas="miDia.porPlanear.hasNextPage.value"
        :cargando-mas="miDia.porPlanear.isFetchingNextPage.value"
        @planear="miDia.planear"
        @devolver="devolver"
        @ver-mas="miDia.porPlanear.fetchNextPage()"
      />

      <section class="tarjeta mi-dia__centro" aria-labelledby="titulo-del-periodo">
        <header class="tarjeta__cabecera">
          <div class="mi-dia__navegacion">
            <v-btn
              :icon="iconos.anterior"
              variant="text"
              size="small"
              aria-label="Anterior"
              @click="miDia.moverPeriodo(-1)"
            />
            <h2 id="titulo-del-periodo" class="tarjeta__titulo mi-dia__titulo" aria-live="polite">{{ titulo }}</h2>
            <v-btn
              :icon="iconos.siguiente"
              variant="text"
              size="small"
              aria-label="Siguiente"
              @click="miDia.moverPeriodo(1)"
            />
            <v-btn v-if="dia !== hoy" variant="text" size="small" @click="miDia.irADia(hoy)">Hoy</v-btn>
          </div>
          <div v-if="vista === 'dia'" class="mi-dia__carga">
            <span class="nota num">{{ resumen }}</span>
            <span class="mi-dia__barra" aria-hidden="true">
              <span class="mi-dia__planeado" :style="{ transform: escala(delDia.length) }" />
              <span class="mi-dia__hecho" :style="{ transform: escala(hechos) }" />
            </span>
            <v-btn
              size="small"
              :variant="casiVacio ? 'flat' : 'text'"
              color="primary"
              :loading="miDia.escribiendo.value"
              @click="miDia.sugerir()"
              >Sugerir mi día</v-btn
            >
          </div>
        </header>

        <MensajeEnPagina
          v-if="miDia.plan.error.value"
          tipo="error"
          titulo="No se pudo cargar su agenda"
          reintentable
          @reintentar="miDia.plan.refetch()"
        />
        <template v-else>
          <div v-if="vista === 'dia' && pendientes.length" class="mi-dia__franja">
            <v-icon :icon="iconos.advertencia" size="16" aria-hidden="true" />
            <span class="mi-dia__franja-texto">
              <b>{{ pendientes.length }} pendiente{{ pendientes.length > 1 ? 's' : '' }} de días anteriores:</b>
              <CodigoTicket :codigo="pendientes[0].codigo" /> {{ pendientes[0].titulo }}
            </span>
            <v-btn
              variant="text"
              size="small"
              color="primary"
              @click="miDia.planear({ tipo: 'incidente', id: pendientes[0].id }, hoy)"
              >Pasar a hoy</v-btn
            >
          </div>
          <AgendaDelDia
            v-if="vista === 'dia'"
            :key="dia"
            :entradas="delDia"
            :es-hoy="esHoy"
            :minuto-actual="miDia.minutoActual.value"
            :ahora-id="enCurso.ahora?.id"
            :opciones="opcionesRapidas"
            @soltar="alSoltarEnElDia"
            @duracion="alCambiarDuracion"
            @quitar="miDia.quitar"
            @bloquear="alBloquear"
            @desbloquear="alDesbloquear"
            @manana="pasarAManana"
            @marcar="miDia.marcarNota"
            @elegir="alElegirEnHueco"
            @crear-nota="miDia.crearNota"
          />
          <SemanaDelPlan
            v-else-if="vista === 'semana'"
            :dias="semanaLaboral(dia)"
            :entradas="miDia.entradas.value"
            :hoy="hoy"
            @abrir-dia="miDia.irADia"
            @soltar="soltar"
          />
          <MesDelPlan
            v-else
            :casillas="cuadriculaDelMes(dia)"
            :mes="dia"
            :entradas="miDia.entradas.value"
            :hoy="hoy"
            @abrir-dia="miDia.irADia"
          />
        </template>
      </section>

      <PizarraNotas
        ref="pizarra"
        :notas="notasSueltas"
        :hoy="hoy"
        @crear="miDia.crearNota"
        @marcar="miDia.marcarNota"
        @borrar="miDia.borrarNota"
        @planear="miDia.planear"
        @devolver="devolver"
      />
    </div>

    <DialogoTexto
      v-model="dialogoDeBloqueo"
      titulo="¿Por qué está bloqueado?"
      etiqueta="Motivo"
      accion="Guardar"
      :maximo="120"
      @confirmar="confirmarBloqueo"
    />
  </div>
</template>

<style scoped>
.pagina--mi-dia {
  max-width: 1440px;
}

/* Tres columnas, como un escritorio: por planear, el día y las notas a mano. */
.mi-dia {
  display: grid;
  grid-template-columns: 350px minmax(0, 1fr) 220px;
  gap: 16px;
  align-items: start;
}

.mi-dia__centro {
  min-width: 0;
}

.mi-dia__navegacion,
.mi-dia__carga {
  display: flex;
  gap: 8px;
  align-items: center;
}

.mi-dia__titulo {
  min-width: 170px;
  text-align: center;
}

.mi-dia__barra {
  position: relative;
  width: 110px;
  height: 6px;
  overflow: hidden;
  background: var(--resalte);
  border-radius: 3px;
}

.mi-dia__planeado,
.mi-dia__hecho {
  position: absolute;
  inset: 0;
  transform-origin: left;
  transition: transform var(--media) var(--curva);
}

.mi-dia__planeado {
  background: var(--acento-borde);
}

.mi-dia__hecho {
  background: var(--acento);
}

.mi-dia__franja {
  display: flex;
  gap: 10px;
  align-items: center;
  min-height: 40px;
  padding: 4px 8px 4px 16px;
  color: var(--advertencia);
  font-size: 13px;
  background: var(--sutil);
  border-bottom: 1px solid var(--borde);
}

.mi-dia__franja-texto {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  color: var(--texto);
  white-space: nowrap;
  text-overflow: ellipsis;
}

.mi-dia__franja-texto b {
  color: var(--advertencia);
  font-weight: 600;
}

@media (width <= 1200px) {
  .mi-dia {
    grid-template-columns: 350px minmax(0, 1fr);
  }

  .mi-dia > :last-child {
    grid-column: 1 / -1;
  }
}

@media (width <= 900px) {
  .mi-dia {
    grid-template-columns: 1fr;
  }

  .mi-dia__carga {
    flex-wrap: wrap;
  }
}
</style>
