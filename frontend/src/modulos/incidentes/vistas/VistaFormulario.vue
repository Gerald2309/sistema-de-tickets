<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import SelectorFechaHora from '@/compartido/componentes/SelectorFechaHora.vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { iconos } from '@/compartido/iconos.js'
import { useBuscadorDePersonas } from '@/modulos/identidad/index.js'
import CodigoTicket from '../componentes/CodigoTicket.vue'
import { usePestanas } from '../tienda/pestanas.js'
import { MAXIMO_DESCRIPCION, MAXIMO_TITULO, useFormularioIncidente } from '../composables/useFormularioIncidente.js'
import { CATEGORIAS, PRIORIDADES, nombreDeCategoria, presentarPrioridad } from '../presentacion.js'

const props = defineProps({ id: { type: Number, default: undefined } })

const formulario = useFormularioIncidente(() => props.id)
const { campos, creando, habilitado, erroresDe, tocar, alEscribir } = formulario
const confirmacion = useConfirmacion()
const pestanas = usePestanas()
const raiz = ref(/** @type {HTMLElement | null} */ (null))

const elegidos = computed(() => (campos.asignadoId ? [campos.asignadoId] : []))
const personas = useBuscadorDePersonas({ elegidos })
/** @param {string | null} texto */
const buscarPersona = (texto) => (personas.texto.value = texto ?? '')

// Las opciones salen del catálogo de la API (API 4), en el orden de la interfaz; mientras llega, las conocidas.
const prioridades = computed(() => {
  const validas = formulario.catalogos.data.value?.prioridades ?? Object.keys(PRIORIDADES)
  return Object.keys(PRIORIDADES)
    .filter((valor) => validas.includes(valor))
    .map((valor) => ({ value: valor, title: presentarPrioridad(valor).nombre }))
})
const categorias = computed(() =>
  (formulario.catalogos.data.value?.categorias ?? Object.keys(CATEGORIAS)).map((valor) => ({
    value: valor,
    title: nombreDeCategoria(valor),
  })),
)

const MOTIVO = 'Su rol no permite cambiar este dato.'
/** @param {import('../composables/useFormularioIncidente.js').Campo} campo */
const ayudaDe = (campo, ayuda = '') => (habilitado(campo) ? ayuda : MOTIVO)

const titulo = computed(() => (creando.value ? 'Nuevo incidente' : 'Editar incidente'))
const volver = computed(() =>
  creando.value ? { name: 'incidentes' } : { name: 'incidente', params: { id: props.id } },
)

/** Al enviar con errores, el primer campo con error recibe el foco y se agita (RF-F37). */
async function enviar() {
  const conError = await formulario.guardar()
  if (!conError) {
    return
  }
  await nextTick()
  const contenedor = raiz.value?.querySelector(`[data-campo="${conError}"]`)
  contenedor?.querySelector(/** @type {'input'} */ ('input, textarea'))?.focus()
  contenedor?.classList.remove('agita')
  requestAnimationFrame(() => contenedor?.classList.add('agita'))
}

// Cambios sin guardar (RF-F39): salir a otra ruta o pestaña pregunta; cerrar o recargar, el navegador avisa.
onBeforeRouteLeave(async () => {
  // Si su pestaña ya se cerró, la confirmación ya se pidió al cerrarla.
  const pestanaCerrada = props.id !== undefined && !pestanas.buscar(props.id)
  if (!formulario.modificado.value || formulario.guardando.value || pestanaCerrada) {
    return true
  }
  const confirmado = await confirmacion.pedir({
    titulo: 'Hay cambios sin guardar',
    texto: 'Si sale ahora, se pierde lo que escribió.',
    accion: 'Salir sin guardar',
    peligrosa: true,
  })
  if (confirmado) {
    formulario.descartar()
  }
  return confirmado
})
/** @param {BeforeUnloadEvent} evento */
const alSalirDelNavegador = (evento) => formulario.modificado.value && evento.preventDefault()
onMounted(() => window.addEventListener('beforeunload', alSalirDelNavegador))
onBeforeUnmount(() => window.removeEventListener('beforeunload', alSalirDelNavegador))
</script>

<template>
  <div ref="raiz" class="pagina pagina--angosta">
    <nav class="migas entra" aria-label="Migas de pan">
      <RouterLink :to="{ name: 'incidentes' }">Incidentes</RouterLink>
      <v-icon :icon="iconos.siguiente" size="14" />
      <template v-if="!creando && formulario.original.data.value">
        <RouterLink :to="volver"><CodigoTicket :codigo="formulario.original.data.value.codigo" /></RouterLink>
        <v-icon :icon="iconos.siguiente" size="14" />
      </template>
      <span aria-current="page">{{ creando ? 'Nuevo' : 'Editar' }}</span>
    </nav>
    <header class="cabecera">
      <h1 v-revelar class="titulo-pagina">{{ titulo }}</h1>
    </header>

    <MensajeEnPagina
      v-if="!creando && formulario.original.isError.value"
      tipo="error"
      titulo="No se pudo cargar el incidente"
      reintentable
      @reintentar="formulario.original.refetch()"
    >
      {{ formulario.original.error.value?.message }}
    </MensajeEnPagina>

    <div v-else-if="!formulario.cargado.value" class="tarjeta formulario__cargando" aria-busy="true">
      <EsqueletoDeCarga v-for="n in 4" :key="n" alto="48px" />
    </div>

    <form v-else class="tarjeta entra" style="--i: 3" novalidate @submit.prevent="enviar">
      <div class="formulario-por-secciones">
        <MensajeEnPagina v-if="formulario.errorGeneral.value" tipo="error" titulo="No se pudo guardar" class="mb-4">
          {{ formulario.errorGeneral.value }}
        </MensajeEnPagina>

        <section class="seccion">
          <div>
            <h2 class="seccion__titulo">Información</h2>
            <p class="seccion__ayuda">Qué ocurre, con el detalle que necesite soporte.</p>
          </div>
          <div class="formulario__campos">
            <div data-campo="titulo">
              <v-text-field
                v-model="campos.titulo"
                label="Título *"
                :counter="MAXIMO_TITULO"
                :maxlength="MAXIMO_TITULO"
                :disabled="!habilitado('titulo')"
                :hint="ayudaDe('titulo')"
                persistent-hint
                :error-messages="erroresDe('titulo')"
                autofocus
                @blur="tocar('titulo')"
                @update:model-value="alEscribir('titulo')"
              />
            </div>
            <div data-campo="descripcion">
              <v-textarea
                v-model="campos.descripcion"
                label="Descripción *"
                rows="5"
                auto-grow
                :counter="MAXIMO_DESCRIPCION"
                :maxlength="MAXIMO_DESCRIPCION"
                :disabled="!habilitado('descripcion')"
                :hint="ayudaDe('descripcion')"
                persistent-hint
                :error-messages="erroresDe('descripcion')"
                @blur="tocar('descripcion')"
                @update:model-value="alEscribir('descripcion')"
              />
            </div>
          </div>
        </section>

        <section class="seccion">
          <div>
            <h2 class="seccion__titulo">Clasificación</h2>
            <p class="seccion__ayuda">Define la urgencia y el tipo de incidente.</p>
          </div>
          <div class="formulario__par">
            <div data-campo="prioridad">
              <v-select
                v-model="campos.prioridad"
                label="Prioridad *"
                :items="prioridades"
                :disabled="!habilitado('prioridad')"
                :hint="ayudaDe('prioridad')"
                persistent-hint
                :error-messages="erroresDe('prioridad')"
                @update:model-value="alEscribir('prioridad')"
              />
            </div>
            <div data-campo="categoria">
              <v-select
                v-model="campos.categoria"
                label="Categoría *"
                :items="categorias"
                :disabled="!habilitado('categoria')"
                :hint="ayudaDe('categoria')"
                persistent-hint
                :error-messages="erroresDe('categoria')"
                @update:model-value="alEscribir('categoria')"
              />
            </div>
          </div>
        </section>

        <section class="seccion seccion--ultima">
          <div>
            <h2 class="seccion__titulo">Asignación y plazo</h2>
            <p class="seccion__ayuda">Quién lo atiende y hasta cuándo.</p>
          </div>
          <div class="formulario__par">
            <div data-campo="asignadoId">
              <v-autocomplete
                v-model="campos.asignadoId"
                label="Asignar a"
                :items="personas.personas.value"
                item-title="nombre"
                item-value="id"
                clearable
                no-filter
                :loading="personas.cargando.value"
                no-data-text="Escriba un nombre"
                :disabled="!habilitado('asignadoId')"
                :hint="ayudaDe('asignadoId', 'Opcional. Puede asignarlo después.')"
                persistent-hint
                :error-messages="erroresDe('asignadoId')"
                @update:search="buscarPersona"
                @update:model-value="alEscribir('asignadoId')"
              />
            </div>
            <div data-campo="vencimiento">
              <SelectorFechaHora
                v-model="campos.vencimiento"
                etiqueta="Fecha de vencimiento *"
                :deshabilitado="!habilitado('vencimiento')"
                :ayuda="habilitado('vencimiento') ? '' : MOTIVO"
                :errores="erroresDe('vencimiento')"
                @blur="tocar('vencimiento')"
                @update:model-value="alEscribir('vencimiento')"
              />
            </div>
          </div>
        </section>
      </div>
      <footer class="formulario__pie">
        <v-btn variant="text" :to="volver">Cancelar</v-btn>
        <BotonEnvio :texto="creando ? 'Crear incidente' : 'Guardar cambios'" :pendiente="formulario.guardando.value" />
      </footer>
    </form>
  </div>
</template>

<style scoped>
.formulario__cargando {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 24px 28px;
}

.formulario__campos {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.formulario__par {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px 12px;
}

.formulario__pie {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  padding: 16px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

@media (width <= 600px) {
  .formulario__par {
    grid-template-columns: 1fr;
  }
}
</style>
