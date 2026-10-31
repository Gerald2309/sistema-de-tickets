<script setup>
import { computed, ref } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import { useSesion } from '@/modulos/identidad/index.js'
import AsignacionIncidente from '../componentes/AsignacionIncidente.vue'
import CasosSimilares from '../componentes/CasosSimilares.vue'
import CodigoTicket from '../componentes/CodigoTicket.vue'
import TarjetaConfirmarSolucion from '../componentes/TarjetaConfirmarSolucion.vue'
import ConversacionIncidente from '../componentes/ConversacionIncidente.vue'
import DialogoRelato from '../componentes/DialogoRelato.vue'
import DialogoResolver from '../componentes/DialogoResolver.vue'
import HistorialIncidente from '../componentes/HistorialIncidente.vue'
import InsigniaEstado from '../componentes/InsigniaEstado.vue'
import InsigniaPrioridad from '../componentes/InsigniaPrioridad.vue'
import ProgresoSolicitud from '../componentes/ProgresoSolicitud.vue'
import PropiedadesIncidente from '../componentes/PropiedadesIncidente.vue'
import TarjetaRelacionados from '../componentes/TarjetaRelacionados.vue'
import { useDetalleIncidente } from '../composables/useIncidente.js'
import { rutaDelListado } from '../filtros.js'
import { nombreDeCategoria, presentarEstado } from '../presentacion.js'

const props = defineProps({ id: { type: Number, required: true } })

const sesion = useSesion()
const detalle = useDetalleIncidente(() => props.id)
const { incidente, partes } = detalle

const resolviendo = ref(false)
const conSolucion = computed(
  () => (incidente.value?.estado === 'resuelto' || incidente.value?.estado === 'cerrado') && incidente.value.solucion,
)

/** @param {number | null} asignadoId */
const asignar = (asignadoId) => detalle.ajustar({ asignadoId })

// Quien lo reportó corrige el título y la descripción mientras nadie lo ha tomado.
const corrigiendo = ref(false)
const guardandoRelato = ref(false)
/** @param {{ titulo?: string, descripcion?: string }} cambios */
async function corregirRelato(cambios) {
  guardandoRelato.value = true
  if (await detalle.ajustar(cambios)) {
    corrigiendo.value = false
  }
  guardandoRelato.value = false
}

/** @param {string} estado */
async function elegirEstado(estado) {
  if (estado === 'resuelto') {
    resolviendo.value = true
    return
  }
  await detalle.cambiarEstado(estado)
}

/** @param {string} solucion */
async function resolver(solucion) {
  if (await detalle.cambiarEstado('resuelto', solucion)) {
    resolviendo.value = false
  }
}
</script>

<template>
  <div class="pagina">
    <MensajeEnPagina
      v-if="detalle.detalle.isError.value && !incidente"
      tipo="error"
      titulo="No se pudo cargar el incidente"
      reintentable
      @reintentar="detalle.detalle.refetch()"
    >
      {{ detalle.detalle.error.value?.message }}
    </MensajeEnPagina>

    <div v-else-if="!incidente" class="detalle__cargando" aria-busy="true">
      <EsqueletoDeCarga ancho="120px" />
      <EsqueletoDeCarga ancho="55%" alto="28px" />
      <EsqueletoDeCarga alto="160px" />
    </div>

    <template v-else>
      <nav class="migas entra" aria-label="Migas de pan">
        <RouterLink :to="{ name: 'incidentes' }">Incidentes</RouterLink>
        <v-icon :icon="iconos.siguiente" size="14" />
        <CodigoTicket :codigo="incidente.codigo" aria-current="page" />
      </nav>
      <header class="cabecera">
        <div class="detalle__encabezado">
          <h1 v-revelar class="titulo-pagina detalle__titulo">{{ incidente.titulo }}</h1>
          <div class="detalle__insignias entra" style="--i: 2">
            <Transition name="texto" mode="out-in">
              <InsigniaEstado :key="incidente.estado" :estado="incidente.estado" caja />
            </Transition>
            <InsigniaPrioridad :prioridad="incidente.prioridad" caja />
            <span class="insignia">{{ nombreDeCategoria(incidente.categoria) }}</span>
            <span class="nota">
              creado {{ tiempoRelativo(incidente.creadoEn).texto }} por {{ incidente.creador.nombre }}
              <template v-if="incidente.cliente"> · {{ incidente.cliente.nombre }}</template>
            </span>
          </div>
        </div>
        <div class="detalle__acciones entra" style="--i: 2">
          <v-btn
            v-if="detalle.puedeTomar.value"
            color="primary"
            variant="flat"
            :prepend-icon="iconos.tomar"
            :loading="detalle.tomando.value"
            @click="detalle.tomar"
            >Tomar</v-btn
          >
          <v-menu v-if="partes.estado">
            <template #activator="{ props: activador }">
              <v-btn
                v-bind="activador"
                color="primary"
                variant="flat"
                :append-icon="iconos.desplegar"
                :disabled="!incidente.estadosSiguientes.length"
                :loading="detalle.cambiandoEstado.value"
              >
                Cambiar estado
              </v-btn>
            </template>
            <v-list min-width="220">
              <v-list-subheader>Pasar a</v-list-subheader>
              <v-list-item
                v-for="estado in incidente.estadosSiguientes"
                :key="estado"
                :title="presentarEstado(estado).nombre"
                @click="elegirEstado(estado)"
              >
                <template #prepend
                  ><span class="punto" :style="{ background: presentarEstado(estado).color }"
                /></template>
              </v-list-item>
            </v-list>
          </v-menu>
          <v-btn
            v-if="detalle.puedeEliminar.value"
            variant="outlined"
            color="error"
            :icon="iconos.eliminar"
            class="detalle__eliminar"
            :loading="detalle.eliminando.value"
            aria-label="Eliminar"
            @click="detalle.eliminar"
          />
        </div>
      </header>

      <div class="detalle__rejilla">
        <div class="detalle__principal">
          <section class="tarjeta entra" style="--i: 3" aria-labelledby="titulo-descripcion">
            <header class="tarjeta__cabecera">
              <h2 id="titulo-descripcion" class="tarjeta__titulo">Descripción</h2>
              <v-btn
                v-if="partes.relato"
                variant="text"
                size="small"
                color="primary"
                :prepend-icon="iconos.editar"
                @click="corrigiendo = true"
                >Corregir</v-btn
              >
            </header>
            <p class="tarjeta__cuerpo detalle__texto">{{ incidente.descripcion }}</p>
          </section>
          <DialogoRelato
            v-if="partes.relato"
            v-model="corrigiendo"
            :titulo="incidente.titulo"
            :descripcion="incidente.descripcion"
            :pendiente="guardandoRelato"
            @confirmar="corregirRelato"
          />
          <section
            v-if="conSolucion"
            class="tarjeta detalle__solucion entra"
            style="--i: 3"
            aria-labelledby="titulo-solucion"
          >
            <header class="tarjeta__cabecera">
              <h2 id="titulo-solucion" class="tarjeta__titulo">
                <v-icon :icon="iconos.exito" size="16" class="mr-1" />Solución
              </h2>
            </header>
            <p class="tarjeta__cuerpo detalle__texto">{{ incidente.solucion }}</p>
          </section>
          <TarjetaConfirmarSolucion
            v-if="detalle.puedeConfirmar.value"
            :pendiente="detalle.confirmando.value"
            class="entra"
            style="--i: 3"
            @confirmar="detalle.confirmar"
          />
          <ConversacionIncidente :key="incidente.id" :incidente="incidente" class="entra" style="--i: 4" />
          <CasosSimilares
            v-if="detalle.veTodo.value"
            :casos="detalle.similares.data.value"
            :cargando="detalle.similares.isPending.value"
            class="entra"
            style="--i: 4"
          />
          <HistorialIncidente
            v-if="sesion.tiene('ver_historial')"
            :entradas="detalle.entradasDelHistorial.value"
            :cargando="detalle.historial.isPending.value"
            :hay-mas="detalle.historial.hasNextPage.value"
            :cargando-mas="detalle.historial.isFetchingNextPage.value"
            class="entra"
            style="--i: 5"
            @ver-mas="detalle.historial.fetchNextPage()"
          />
        </div>

        <aside class="detalle__lateral">
          <!-- Quien solo ve lo suyo sigue el avance de su solicitud; soporte ve y reparte la asignación. -->
          <ProgresoSolicitud v-if="!detalle.veTodo.value" :incidente="incidente" class="entra" style="--i: 3" />
          <AsignacionIncidente
            v-else
            :asignado="incidente.asignado"
            :editable="partes.asignacion"
            :quitable="detalle.puedeDejarSinAsignar.value"
            :pendiente="detalle.ajustando.value"
            class="entra"
            style="--i: 3"
            @asignar="asignar"
          />
          <PropiedadesIncidente
            :incidente="incidente"
            :partes="partes"
            :pendiente="detalle.ajustando.value"
            :vista-del-solicitante="!detalle.veTodo.value"
            class="entra"
            style="--i: 4"
            @ajustar="detalle.ajustar"
          />

          <TarjetaRelacionados
            v-if="detalle.veTodo.value"
            class="entra"
            style="--i: 5"
            :titulo="`Otros de ${incidente.creador.nombre}`"
            :elementos="detalle.otrosDelSolicitante.value.elementos"
            :total="detalle.otrosDelSolicitante.value.total"
            :enlace="rutaDelListado({ creador: incidente.creador.id })"
            :texto-del-enlace="`Ver todos los de ${incidente.creador.nombre}`"
            vacio="No tiene otros incidentes."
          />

          <section
            v-if="incidente.cliente && detalle.empresa.value"
            class="tarjeta entra"
            style="--i: 6"
            aria-labelledby="titulo-empresa"
          >
            <header class="tarjeta__cabecera">
              <h2 id="titulo-empresa" class="tarjeta__titulo">{{ incidente.cliente.nombre }}</h2>
              <v-icon :icon="iconos.empresa" size="18" class="detalle__icono-empresa" />
            </header>
            <div class="tarjeta__cuerpo detalle__cifras">
              <div>
                <b class="num detalle__cifra">{{ detalle.empresa.value.total }}</b>
                <div class="nota">incidentes</div>
              </div>
              <div>
                <b class="num detalle__cifra">{{ detalle.empresa.value.sinResolver }}</b>
                <div class="nota">sin resolver</div>
              </div>
            </div>
            <RouterLink :to="rutaDelListado({ cliente: incidente.cliente.id })" class="detalle__pie"
              >Ver los de {{ incidente.cliente.nombre }} →</RouterLink
            >
          </section>
        </aside>
      </div>

      <DialogoResolver
        v-model="resolviendo"
        :solucion-anterior="incidente.solucion ?? ''"
        :pendiente="detalle.cambiandoEstado.value"
        :error="detalle.errorDeEstado.value"
        @confirmar="resolver"
      />
    </template>
  </div>
</template>

<style scoped>
.detalle__cargando {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detalle__encabezado {
  min-width: 0;
}

.detalle__titulo {
  overflow-wrap: anywhere;
}

.detalle__insignias {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 12px;
}

.insignia {
  display: inline-flex;
  align-items: center;
  height: 24px;
  padding: 0 9px;
  font-size: 12.5px;
  font-weight: 500;
  background: var(--sutil);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.detalle__acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.detalle__eliminar {
  width: 36px;
  height: 36px;
  border-radius: var(--radio);
}

.detalle__rejilla {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 12px;
  align-items: start;
}

.detalle__principal,
.detalle__lateral {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.detalle__texto {
  margin: 0;
  line-height: 1.65;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.detalle__solucion {
  border-left: 3px solid var(--estado-resuelto);
}

.detalle__icono-empresa {
  color: var(--suave);
}

.detalle__cifras {
  display: flex;
  gap: 24px;
}

.detalle__cifra {
  font-size: 22px;
}

.detalle__pie {
  display: block;
  padding: 9px 16px;
  color: var(--acento);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  border-top: 1px solid var(--borde);
}

.detalle__pie:hover {
  text-decoration: underline;
}

.punto {
  margin-right: 12px;
}

@media (width <= 960px) {
  .detalle__rejilla {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
