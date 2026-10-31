<script setup>
import { computed } from 'vue'
import ChipsDeFiltros from '@/compartido/componentes/ChipsDeFiltros.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import MenuFiltro from '@/compartido/componentes/MenuFiltro.vue'
import MenuMasFiltros from '@/compartido/componentes/MenuMasFiltros.vue'
import RangoDeFechas from '@/compartido/componentes/RangoDeFechas.vue'
import PieDeTabla from '@/compartido/componentes/PieDeTabla.vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { useBuscadorDePersonas } from '@/modulos/identidad/index.js'
import { useAuditoria } from '../composables/useAuditoria.js'
import { ACCIONES, presentarAccion } from '../presentacion.js'

const { filtros, lista, cambiar } = useAuditoria()
const elegidas = computed(() => [filtros.value.usuario, filtros.value.afectado].filter((id) => id !== undefined))
const personas = useBuscadorDePersonas({ incluirInactivas: true, elegidos: elegidas })

const opcionesDeAccion = Object.entries(ACCIONES).map(([valor, { nombre, icono }]) => ({ valor, nombre, icono }))

const chips = computed(() => {
  const f = filtros.value
  return [
    ...(f.accion ? [{ clave: 'accion', etiqueta: `Acción: ${presentarAccion(f.accion).nombre}` }] : []),
    ...(f.usuario ? [{ clave: 'usuario', etiqueta: `Quién: ${personas.nombreDe(f.usuario) ?? '…'}` }] : []),
    ...(f.afectado ? [{ clave: 'afectado', etiqueta: `Afectado: ${personas.nombreDe(f.afectado) ?? '…'}` }] : []),
    ...(f.desde ? [{ clave: 'desde', etiqueta: `Desde ${f.desde}` }] : []),
    ...(f.hasta ? [{ clave: 'hasta', etiqueta: `Hasta ${f.hasta}` }] : []),
  ]
})

/** @param {string | null} texto */
const buscarPersona = (texto) => (personas.texto.value = texto ?? '')
/** @param {string} clave */
const quitar = (clave) => cambiar({ [clave]: undefined })
const limpiar = () =>
  cambiar({ accion: undefined, usuario: undefined, afectado: undefined, desde: undefined, hasta: undefined })
</script>

<template>
  <div class="pagina">
    <div class="migas entra">Administración</div>
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Auditoría</h1>
        <p class="subtitulo entra" style="--i: 2">
          Acciones de administración e inicios de sesión fallidos. Solo lectura; lo más reciente primero.
        </p>
      </div>
    </header>

    <div class="barra-filtros entra" style="--i: 3">
      <MenuFiltro
        :model-value="filtros.accion"
        etiqueta="Acción"
        :opciones="opcionesDeAccion"
        @update:model-value="cambiar({ accion: $event })"
      />
      <MenuMasFiltros>
        <v-autocomplete
          :model-value="filtros.usuario"
          :items="personas.personas.value"
          item-title="nombre"
          item-value="id"
          label="Quién lo hizo"
          clearable
          hide-details
          no-filter
          :loading="personas.cargando.value"
          no-data-text="Escriba un nombre"
          @update:search="buscarPersona"
          @update:model-value="cambiar({ usuario: $event ?? undefined })"
        />
        <v-autocomplete
          :model-value="filtros.afectado"
          :items="personas.personas.value"
          item-title="nombre"
          item-value="id"
          label="A quién afectó"
          clearable
          hide-details
          no-filter
          no-data-text="Escriba un nombre"
          @update:search="buscarPersona"
          @update:model-value="cambiar({ afectado: $event ?? undefined })"
        />
        <RangoDeFechas
          :desde="filtros.desde"
          :hasta="filtros.hasta"
          @update:desde="cambiar({ desde: $event })"
          @update:hasta="cambiar({ hasta: $event })"
        />
      </MenuMasFiltros>
      <ChipsDeFiltros :filtros="chips" @quitar="quitar" @limpiar="limpiar" />
    </div>

    <MensajeEnPagina
      v-if="lista.isError.value"
      tipo="error"
      titulo="No se pudo cargar la auditoría"
      reintentable
      class="mb-3"
      @reintentar="lista.refetch()"
    >
      {{ lista.error.value?.message }}
    </MensajeEnPagina>

    <div class="tarjeta entra" style="--i: 4">
      <div v-if="lista.data.value && !lista.data.value.elementos.length" class="auditoria__vacio">
        <EstadoVacio :icono="iconos.auditoria" titulo="No hay registros con estos filtros" />
      </div>
      <v-table v-else v-desplazable="'Registros de auditoría'" density="compact">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Acción</th>
            <th>Quién</th>
            <th>Afectado</th>
            <th>Detalle</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody v-if="!lista.data.value">
          <tr v-for="fila in 8" :key="fila">
            <td><EsqueletoDeCarga ancho="110px" /></td>
            <td><EsqueletoDeCarga ancho="150px" /></td>
            <td><EsqueletoDeCarga ancho="110px" /></td>
            <td><EsqueletoDeCarga ancho="110px" /></td>
            <td><EsqueletoDeCarga :ancho="`${120 + fila * 10}px`" /></td>
            <td><EsqueletoDeCarga ancho="80px" /></td>
          </tr>
        </tbody>
        <TransitionGroup v-else name="fila" tag="tbody">
          <tr v-for="registro in lista.data.value.elementos" :key="registro.id">
            <td class="num text-no-wrap tenue-suave">{{ fechaHora(registro.creadoEn) }}</td>
            <td class="text-no-wrap" :class="{ 'accion--alerta': presentarAccion(registro.accion).alerta }">
              <v-icon :icon="presentarAccion(registro.accion).icono" size="16" class="mr-2 accion__icono" />
              {{ presentarAccion(registro.accion).nombre }}
            </td>
            <td class="text-no-wrap">
              <span v-if="registro.usuario">{{ registro.usuario.nombre }}</span>
              <span v-else class="tenue">Sin sesión</span>
            </td>
            <td class="text-no-wrap">
              <span v-if="registro.afectado">{{ registro.afectado.nombre }}</span>
              <span v-else class="tenue">—</span>
            </td>
            <td class="auditoria__detalle">{{ registro.detalle ?? '' }}</td>
            <td class="num text-no-wrap tenue-suave auditoria__ip">{{ registro.ip ?? '—' }}</td>
          </tr>
        </TransitionGroup>
      </v-table>
      <PieDeTabla
        v-if="lista.data.value && lista.data.value.total > 0"
        :pagina="lista.data.value.pagina"
        :ultima-pagina="lista.data.value.ultimaPagina"
        :por-pagina="25"
        :total="lista.data.value.total"
        :en-pagina="lista.data.value.elementos.length"
        :tamanos="[25]"
        @update:pagina="cambiar({ pagina: $event })"
      />
    </div>
  </div>
</template>

<style scoped>
.tenue {
  color: var(--tenue);
}

.tenue-suave {
  color: var(--suave);
}

.accion__icono {
  color: var(--suave);
}

.accion--alerta,
.accion--alerta .accion__icono {
  color: var(--error);
}

.auditoria__detalle {
  font-size: 13px;
}

.auditoria__ip {
  font-size: 12.5px;
}

.auditoria__vacio {
  padding: 16px;
}
</style>
