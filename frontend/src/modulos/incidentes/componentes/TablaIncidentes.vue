<script setup>
import { useDisplay } from 'vuetify'
import AvatarPersona from '@/compartido/componentes/AvatarPersona.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import { iconos } from '@/compartido/iconos.js'
import { nombreDeCategoria } from '../presentacion.js'
import CodigoTicket from './CodigoTicket.vue'
import InsigniaEstado from './InsigniaEstado.vue'
import InsigniaPrioridad from './InsigniaPrioridad.vue'
import IndicadorDeVencimiento from './IndicadorDeVencimiento.vue'

/**
 * Tabla de incidentes (RF-F19, RF-F23, RF-F24): densa, ordenable por encabezado, con selección para el lote. En
 * teléfono, cada fila pasa a tarjeta (RNF-F27).
 */
const props = defineProps({
  elementos: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Incidente[]>} */ (Array),
    default: () => [],
  },
  cargando: { type: Boolean, default: false },
  seleccion: { type: /** @type {import('vue').PropType<Set<number>>} */ (Set), required: true },
  todosSeleccionados: { type: Boolean, default: false },
  seleccionable: { type: Boolean, default: false },
  orden: { type: String, default: 'id' },
  dir: { type: String, default: 'desc' },
  hayFiltros: { type: Boolean, default: false },
  puedeEditar: {
    type: /** @type {import('vue').PropType<(incidente: import('../tipos.js').Incidente) => boolean>} */ (Function),
    default: () => false,
  },
})
const emit = defineEmits(['alternar', 'alternar-todos', 'ordenar', 'precargar', 'limpiar'])
const { xs } = useDisplay()

/** Columnas en orden; las que tienen `campo` se ordenan en el servidor (RF-F23). */
const COLUMNAS = /** @type {const} */ ([
  { titulo: 'Código', campo: 'id' },
  { titulo: 'Título', campo: undefined },
  { titulo: 'Estado', campo: 'estado' },
  { titulo: 'Prioridad', campo: 'prioridad' },
  { titulo: 'Asignado a', campo: undefined },
  { titulo: 'Vence', campo: 'fecha_vencimiento' },
])

/**
 * @param {string} campo
 * @returns {'ascending' | 'descending' | 'none'}
 */
const ordenAria = (campo) => (props.orden === campo ? (props.dir === 'asc' ? 'ascending' : 'descending') : 'none')
const pendiente = (/** @type {string} */ estado) => estado === 'abierto' || estado === 'en_progreso'
</script>

<template>
  <div v-if="!cargando && elementos.length === 0" class="tabla-vacia">
    <EstadoVacio
      :icono="iconos.vacio"
      :titulo="hayFiltros ? 'No hay incidentes con estos filtros' : 'Todavía no hay incidentes'"
      :texto="hayFiltros ? 'Pruebe con otras fechas o quite algún filtro.' : ''"
    >
      <v-btn v-if="hayFiltros" size="small" variant="outlined" @click="emit('limpiar')">Limpiar filtros</v-btn>
    </EstadoVacio>
  </div>

  <!-- Teléfono: tarjetas -->
  <ul v-else-if="xs" class="tarjetas" aria-label="Incidentes">
    <li v-for="incidente in elementos" :key="incidente.id" class="tarjeta-incidente">
      <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="tarjeta-incidente__enlace">
        <span class="tarjeta-incidente__cabecera">
          <CodigoTicket :codigo="incidente.codigo" />
          <IndicadorDeVencimiento
            :fecha="incidente.fechaVencimiento"
            :vencido="incidente.vencido"
            :pendiente="pendiente(incidente.estado)"
          />
        </span>
        <b class="tarjeta-incidente__titulo">{{ incidente.titulo }}</b>
        <span class="tarjeta-incidente__datos">
          <InsigniaEstado :estado="incidente.estado" />
          <InsigniaPrioridad :prioridad="incidente.prioridad" />
        </span>
      </RouterLink>
    </li>
  </ul>

  <v-table v-else class="tabla-incidentes">
    <thead>
      <tr>
        <th v-if="seleccionable" class="columna-seleccion">
          <v-checkbox-btn
            :model-value="todosSeleccionados"
            density="compact"
            aria-label="Seleccionar todos los de esta página"
            @update:model-value="emit('alternar-todos')"
          />
        </th>
        <th
          v-for="columna in COLUMNAS"
          :key="columna.titulo"
          :aria-sort="columna.campo ? ordenAria(columna.campo) : undefined"
        >
          <button v-if="columna.campo" type="button" class="ordenar" @click="emit('ordenar', columna.campo)">
            {{ columna.titulo }}
            <v-icon
              v-if="orden === columna.campo"
              :icon="dir === 'asc' ? iconos.ascendente : iconos.descendente"
              size="14"
            />
          </button>
          <template v-else>{{ columna.titulo }}</template>
        </th>
        <th class="columna-acciones"><span class="solo-lectores">Acciones</span></th>
      </tr>
    </thead>
    <tbody v-if="cargando">
      <tr v-for="fila in 8" :key="fila">
        <td v-if="seleccionable"><EsqueletoDeCarga ancho="16px" alto="16px" /></td>
        <td><EsqueletoDeCarga ancho="84px" /></td>
        <td><EsqueletoDeCarga :ancho="`${160 + fila * 18}px`" /></td>
        <td><EsqueletoDeCarga ancho="76px" /></td>
        <td><EsqueletoDeCarga ancho="64px" /></td>
        <td><EsqueletoDeCarga ancho="96px" /></td>
        <td><EsqueletoDeCarga ancho="64px" /></td>
        <td />
      </tr>
    </tbody>
    <TransitionGroup v-else name="fila" tag="tbody">
      <tr
        v-for="incidente in elementos"
        :key="incidente.id"
        :class="{ 'fila--elegida': seleccion.has(incidente.id) }"
        @mouseenter="emit('precargar', incidente.id)"
      >
        <td v-if="seleccionable" class="columna-seleccion">
          <v-checkbox-btn
            :model-value="seleccion.has(incidente.id)"
            density="compact"
            :aria-label="`Seleccionar ${incidente.codigo}`"
            @update:model-value="emit('alternar', incidente.id)"
          />
        </td>
        <td class="columna-codigo"><CodigoTicket :codigo="incidente.codigo" /></td>
        <td class="columna-titulo">
          <RouterLink :to="{ name: 'incidente', params: { id: incidente.id } }" class="enlace-fila">
            {{ incidente.titulo }}
          </RouterLink>
          <span class="columna-titulo__datos">
            {{ nombreDeCategoria(incidente.categoria)
            }}<template v-if="incidente.cliente"> · {{ incidente.cliente.nombre }}</template>
          </span>
        </td>
        <td><InsigniaEstado :estado="incidente.estado" /></td>
        <td><InsigniaPrioridad :prioridad="incidente.prioridad" /></td>
        <td>
          <AvatarPersona v-if="incidente.asignado" :nombre="incidente.asignado.nombre" />
          <span v-else class="tenue">Sin asignar</span>
        </td>
        <td>
          <IndicadorDeVencimiento
            :fecha="incidente.fechaVencimiento"
            :vencido="incidente.vencido"
            :pendiente="pendiente(incidente.estado)"
          />
        </td>
        <td class="columna-acciones">
          <v-menu location="bottom end">
            <template #activator="{ props: activador }">
              <v-btn
                v-bind="activador"
                :icon="iconos.masAcciones"
                variant="text"
                size="x-small"
                :aria-label="`Acciones de ${incidente.codigo}`"
              />
            </template>
            <v-list min-width="180">
              <v-list-item
                :prepend-icon="iconos.incidente"
                title="Ver detalle"
                :to="{ name: 'incidente', params: { id: incidente.id } }"
              />
              <v-list-item
                v-if="puedeEditar(incidente)"
                :prepend-icon="iconos.editar"
                title="Editar"
                :to="{ name: 'editar-incidente', params: { id: incidente.id } }"
              />
            </v-list>
          </v-menu>
        </td>
      </tr>
    </TransitionGroup>
  </v-table>
</template>

<style scoped>
.columna-seleccion {
  width: 44px;
}

.columna-codigo {
  width: 110px;
}

.columna-titulo {
  width: 100%;
  max-width: 0;
}

.enlace-fila {
  display: block;
  overflow: hidden;
  color: var(--texto);
  font-weight: 500;
  white-space: nowrap;
  text-overflow: ellipsis;
  text-decoration: none;
}

.enlace-fila:hover {
  color: var(--acento);
}

.columna-titulo__datos {
  display: block;
  overflow: hidden;
  color: var(--suave);
  font-size: 12.5px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.fila--elegida {
  background: var(--acento-fondo) !important;
}

.ordenar {
  display: inline-flex;
  gap: 2px;
  align-items: center;
  color: inherit;
  font: inherit;
  letter-spacing: inherit;
  text-transform: inherit;
  background: none;
  border: 0;
  cursor: pointer;
}

.ordenar:hover {
  color: var(--texto);
}

.tenue {
  color: var(--tenue);
}

.tabla-vacia {
  padding: 16px;
}

.tarjetas {
  margin: 0;
  padding: 0;
  list-style: none;
}

.tarjeta-incidente {
  border-bottom: 1px solid var(--borde);
}

.tarjeta-incidente__enlace {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px 16px;
  color: var(--texto);
  text-decoration: none;
}

.tarjeta-incidente__cabecera,
.tarjeta-incidente__datos {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
}

.tarjeta-incidente__titulo {
  font-weight: 600;
}
</style>
