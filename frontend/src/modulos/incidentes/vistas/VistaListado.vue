<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import PieDeTabla from '@/compartido/componentes/PieDeTabla.vue'
import { iconos } from '@/compartido/iconos.js'
import { haceCuanto } from '@/compartido/presentacion/fechas.js'
import { tiempoReal } from '@/compartido/tiempo-real.js'
import { useSesion } from '@/modulos/identidad/index.js'
import BarraFiltrosIncidentes from '../componentes/BarraFiltrosIncidentes.vue'
import BarraLote from '../componentes/BarraLote.vue'
import MenuDescarga from '../componentes/MenuDescarga.vue'
import TablaIncidentes from '../componentes/TablaIncidentes.vue'
import { MAXIMO_DEL_LOTE, useAccionesEnLote } from '../composables/useAccionesEnLote.js'
import { useDescarga } from '../composables/useDescarga.js'
import { usePrecargaDeIncidente } from '../composables/useIncidente.js'
import { useListadoIncidentes } from '../composables/useListadoIncidentes.js'
import { POR_PAGINA, TAMANOS_DE_PAGINA } from '../filtros.js'
import { puedeModificar } from '../permisos.js'

const sesion = useSesion()
const listado = useListadoIncidentes()
const { filtros, pagina, seleccion } = listado
const lote = useAccionesEnLote(() => [...seleccion.value], listado.limpiarSeleccion)
const descarga = useDescarga(() => filtros.value)
const precargar = usePrecargaDeIncidente()

/** @param {import('../tipos.js').Incidente} incidente */
const puedeEditar = (incidente) => puedeModificar(sesion, incidente)
const seleccionable = computed(() => Object.values(lote.permite.value).some(Boolean))
const hayFiltros = computed(() => {
  const { vista: _vista, orden: _orden, dir: _dir, por: _por, pagina: _pagina, ...resto } = filtros.value
  return Object.values(resto).some((valor) => valor !== undefined)
})
const conteos = computed(() => ({
  todos: listado.conteos.todos.data.value,
  paraMi: listado.conteos.paraMi.data.value,
  vencidos: listado.conteos.vencidos.data.value,
}))

// «Actualizado hace…» avanza solo; con el socket conectado, el pie dice «en vivo».
const ahora = ref(Date.now())
const reloj = setInterval(() => (ahora.value = Date.now()), 5000)
onBeforeUnmount(() => clearInterval(reloj))
const actualizado = computed(() => {
  if (tiempoReal.conectado) {
    return 'en vivo'
  }
  return listado.actualizadoEn.value ? `actualizado ${haceCuanto(listado.actualizadoEn.value, ahora.value)}` : ''
})
</script>

<template>
  <div class="pagina">
    <header class="cabecera cabecera--compacta">
      <h1 v-revelar class="titulo-pagina">Incidentes</h1>
      <div class="cabecera__acciones entra" style="--i: 2">
        <MenuDescarga
          :descargando="descarga.descargando.value"
          :progreso="descarga.progreso.value"
          @descargar="descarga.descargar"
        />
        <v-btn
          v-if="sesion.tiene('crear_incidente')"
          color="primary"
          variant="flat"
          :prepend-icon="iconos.crear"
          :to="{ name: 'nuevo-incidente' }"
        >
          Nuevo incidente
        </v-btn>
      </div>
    </header>

    <BarraFiltrosIncidentes
      class="entra"
      style="--i: 3"
      :filtros="filtros"
      :conteos="conteos"
      :ve-todo="listado.veTodo.value"
      :error-de-busqueda="listado.errorDeBusqueda.value"
      @cambiar="listado.cambiar"
      @quitar="listado.quitar"
      @limpiar="listado.limpiar"
    />

    <BarraLote
      v-if="seleccionable"
      :cantidad="seleccion.size"
      :permite="lote.permite.value"
      :pendiente="lote.pendiente.value"
      :maximo="MAXIMO_DEL_LOTE"
      @aplicar="lote.aplicar"
      @eliminar="lote.eliminar"
      @quitar="listado.limpiarSeleccion"
    />

    <MensajeEnPagina
      v-if="listado.errorDeCarga.value"
      tipo="error"
      titulo="No se pudo cargar el listado"
      reintentable
      class="mb-3"
      @reintentar="listado.listado.refetch()"
    >
      {{ listado.errorDeCarga.value.message }}
    </MensajeEnPagina>

    <div class="tarjeta entra" style="--i: 5" :aria-busy="listado.actualizando.value">
      <TablaIncidentes
        :elementos="pagina?.elementos ?? []"
        :cargando="listado.cargando.value && !pagina"
        :seleccion="seleccion"
        :todos-seleccionados="listado.todosSeleccionados.value"
        :seleccionable="seleccionable"
        :orden="filtros.orden ?? 'id'"
        :dir="filtros.dir ?? 'desc'"
        :hay-filtros="hayFiltros"
        :puede-editar="puedeEditar"
        @alternar="listado.alternar"
        @alternar-todos="listado.alternarTodos"
        @ordenar="listado.ordenar"
        @precargar="precargar"
        @limpiar="listado.limpiar"
      />
      <PieDeTabla
        v-if="pagina && pagina.total > 0"
        :pagina="pagina.pagina"
        :ultima-pagina="pagina.ultimaPagina"
        :por-pagina="filtros.por ?? POR_PAGINA"
        :total="pagina.total"
        :en-pagina="pagina.elementos.length"
        :tamanos="TAMANOS_DE_PAGINA"
        :actualizado="actualizado"
        :en-vivo="tiempoReal.conectado"
        @update:pagina="listado.cambiar({ pagina: $event })"
        @update:por-pagina="listado.cambiar({ por: $event })"
      />
    </div>
  </div>
</template>

<style scoped>
.cabecera--compacta {
  align-items: center;
  margin-bottom: 14px;
}

.cabecera__acciones {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
