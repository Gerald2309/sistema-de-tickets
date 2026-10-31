<script setup>
import { computed } from 'vue'
import CampoDeBusqueda from '@/compartido/componentes/CampoDeBusqueda.vue'
import ChipsDeFiltros from '@/compartido/componentes/ChipsDeFiltros.vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import MenuFiltro from '@/compartido/componentes/MenuFiltro.vue'
import MenuMasFiltros from '@/compartido/componentes/MenuMasFiltros.vue'
import RangoDeFechas from '@/compartido/componentes/RangoDeFechas.vue'
import { useBuscadorDePersonas, useEmpresas } from '@/modulos/identidad/index.js'
import { CATEGORIAS, ESTADOS, PRIORIDADES } from '../presentacion.js'

/**
 * Barra de herramientas del listado (RF-F20 a RF-F22): vistas en control segmentado, búsqueda, Estado y
 * Prioridad a la vista y el resto en «Más filtros». Cada filtro activo es un chip con su ×.
 */
const props = defineProps({
  filtros: {
    type: /** @type {import('vue').PropType<import('../tipos.js').FiltrosDeIncidentes>} */ (Object),
    required: true,
  },
  conteos: {
    type: /** @type {import('vue').PropType<{ todos?: number, paraMi?: number, vencidos?: number }>} */ (Object),
    required: true,
  },
  veTodo: { type: Boolean, default: false },
  errorDeBusqueda: { type: String, default: '' },
})
const emit = defineEmits(['cambiar', 'quitar', 'limpiar'])

const elegidas = computed(() => [props.filtros.asignado, props.filtros.creador].filter((id) => id !== undefined))
const personas = useBuscadorDePersonas({ incluirInactivas: true, elegidos: elegidas })
const { empresas, nombreDe: nombreDeEmpresa, permitido: veEmpresas } = useEmpresas()

const vistas = computed(() => [
  { valor: 'todos', etiqueta: 'Todos', conteo: props.conteos.todos },
  ...(props.veTodo ? [{ valor: 'para-mi', etiqueta: 'Para mí', conteo: props.conteos.paraMi }] : []),
  { valor: 'vencidos', etiqueta: 'Vencidos', conteo: props.conteos.vencidos },
])

const opcionesDeEstado = Object.entries(ESTADOS).map(([valor, { nombre, color }]) => ({ valor, nombre, color }))
const opcionesDePrioridad = Object.entries(PRIORIDADES).map(([valor, { nombre, icono, color }]) => ({
  valor,
  nombre,
  icono,
  color,
}))
const opcionesDeCategoria = Object.entries(CATEGORIAS).map(([valor, nombre]) => ({ value: valor, title: nombre }))

/** @param {Partial<import('../tipos.js').FiltrosDeIncidentes>} cambios */
const cambiar = (cambios) => emit('cambiar', cambios)
/** @param {string | null} texto */
const buscarPersona = (texto) => (personas.texto.value = texto ?? '')

const chips = computed(() => {
  const f = props.filtros
  /** @type {{ clave: string, etiqueta: string }[]} */
  const lista = []
  if (f.estado) lista.push({ clave: 'estado', etiqueta: `Estado: ${ESTADOS[f.estado]?.nombre ?? f.estado}` })
  if (f.prioridad)
    lista.push({ clave: 'prioridad', etiqueta: `Prioridad: ${PRIORIDADES[f.prioridad]?.nombre ?? f.prioridad}` })
  if (f.categoria) lista.push({ clave: 'categoria', etiqueta: `Categoría: ${CATEGORIAS[f.categoria] ?? f.categoria}` })
  if (f.asignado) lista.push({ clave: 'asignado', etiqueta: `Asignado a: ${personas.nombreDe(f.asignado) ?? '…'}` })
  if (f.creador) lista.push({ clave: 'creador', etiqueta: `Creado por: ${personas.nombreDe(f.creador) ?? '…'}` })
  if (f.mios) lista.push({ clave: 'mios', etiqueta: 'Creados por mí' })
  if (f.cliente) lista.push({ clave: 'cliente', etiqueta: `Empresa: ${nombreDeEmpresa(f.cliente) ?? '…'}` })
  if (f.vence) lista.push({ clave: 'vence', etiqueta: `Vencen en ${f.vence} h` })
  if (f.desde)
    lista.push({ clave: 'desde', etiqueta: `${f.sobre === 'creacion' ? 'Creados' : 'Vencen'} desde ${f.desde}` })
  if (f.hasta)
    lista.push({ clave: 'hasta', etiqueta: `${f.sobre === 'creacion' ? 'Creados' : 'Vencen'} hasta ${f.hasta}` })
  return lista
})
</script>

<template>
  <div class="barra-filtros">
    <ControlSegmentado
      :model-value="filtros.vista ?? 'todos'"
      :opciones="vistas"
      etiqueta="Vistas"
      class="barra-filtros__vistas"
      @update:model-value="cambiar({ vista: $event })"
    />
    <CampoDeBusqueda
      :model-value="filtros.q ?? ''"
      etiqueta="Buscar por título o descripción"
      :error="errorDeBusqueda"
      @update:model-value="emit('cambiar', { q: $event || undefined }, { reemplazar: true })"
    />
    <MenuFiltro
      :model-value="filtros.estado"
      etiqueta="Estado"
      :opciones="opcionesDeEstado"
      @update:model-value="cambiar({ estado: $event })"
    />
    <MenuFiltro
      :model-value="filtros.prioridad"
      etiqueta="Prioridad"
      :opciones="opcionesDePrioridad"
      @update:model-value="cambiar({ prioridad: $event })"
    />
    <MenuMasFiltros>
      <v-autocomplete
        v-if="veTodo && filtros.vista !== 'para-mi'"
        :model-value="filtros.asignado"
        :items="personas.personas.value"
        item-title="nombre"
        item-value="id"
        label="Asignado a"
        clearable
        hide-details
        no-filter
        :loading="personas.cargando.value"
        no-data-text="Escriba un nombre"
        @update:search="buscarPersona"
        @update:model-value="cambiar({ asignado: $event ?? undefined })"
      />
      <v-autocomplete
        v-if="veTodo"
        :model-value="filtros.creador"
        :items="personas.personas.value"
        item-title="nombre"
        item-value="id"
        label="Creado por"
        clearable
        hide-details
        no-filter
        no-data-text="Escriba un nombre"
        @update:search="buscarPersona"
        @update:model-value="cambiar({ creador: $event ?? undefined, mios: undefined })"
      />
      <v-select
        v-if="veEmpresas"
        :model-value="filtros.cliente"
        :items="empresas"
        item-title="nombre"
        item-value="id"
        label="Empresa"
        clearable
        hide-details
        @update:model-value="cambiar({ cliente: $event ?? undefined })"
      />
      <v-select
        :model-value="filtros.categoria"
        :items="opcionesDeCategoria"
        label="Categoría"
        clearable
        hide-details
        @update:model-value="cambiar({ categoria: $event ?? undefined })"
      />
      <v-select
        :model-value="filtros.sobre ?? 'vencimiento'"
        :items="[
          { value: 'vencimiento', title: 'Fechas de vencimiento' },
          { value: 'creacion', title: 'Fechas de creación' },
        ]"
        label="Las fechas se aplican a"
        hide-details
        @update:model-value="cambiar({ sobre: $event })"
      />
      <RangoDeFechas
        :desde="filtros.desde"
        :hasta="filtros.hasta"
        @update:desde="cambiar({ desde: $event })"
        @update:hasta="cambiar({ hasta: $event })"
      />
    </MenuMasFiltros>
    <ChipsDeFiltros :filtros="chips" @quitar="emit('quitar', $event)" @limpiar="emit('limpiar')" />
  </div>
</template>

<style scoped>
.barra-filtros__vistas {
  margin-right: 8px;
}
</style>
