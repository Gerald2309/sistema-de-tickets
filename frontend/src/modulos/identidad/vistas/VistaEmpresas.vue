<script setup>
import { computed, ref } from 'vue'
import CampoDeBusqueda from '@/compartido/componentes/CampoDeBusqueda.vue'
import DialogoTexto from '@/compartido/componentes/DialogoTexto.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import PieDeTabla from '@/compartido/componentes/PieDeTabla.vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { iconos } from '@/compartido/iconos.js'
import { useEmpresasAdmin } from '../composables/useEmpresasAdmin.js'

/** @typedef {import('../tipos.js').Cliente} Cliente */

const { criterio, lista, cambiar, guardarNombre, alternarActiva, pendiente } = useEmpresasAdmin()
const confirmacion = useConfirmacion()

const MAXIMO_NOMBRE = 150
const editada = ref(/** @type {Cliente | null} */ (null))
const dialogoAbierto = ref(false)
const errorDelDialogo = ref('')
const creando = computed(() => editada.value === null)

/** @param {Cliente | null} empresa */
function abrir(empresa) {
  editada.value = empresa
  errorDelDialogo.value = ''
  dialogoAbierto.value = true
}

/** @param {string} nombre */
async function confirmarNombre(nombre) {
  errorDelDialogo.value = await guardarNombre(editada.value, nombre)
  if (!errorDelDialogo.value) {
    dialogoAbierto.value = false
  }
}

/** @param {Cliente} empresa */
async function cambiarActiva(empresa) {
  if (empresa.activo) {
    const confirmado = await confirmacion.pedir({
      titulo: `¿Desactivar ${empresa.nombre}?`,
      texto: 'Deja de ofrecerse al crear o editar usuarios. Sus personas e incidentes se conservan.',
      accion: 'Desactivar',
      peligrosa: true,
    })
    if (!confirmado) {
      return
    }
  }
  await alternarActiva(empresa)
}
</script>

<template>
  <div class="pagina">
    <div class="migas entra">Administración</div>
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Empresas</h1>
        <p class="subtitulo entra" style="--i: 2">
          Empresas cliente de Psicol. No se eliminan: se desactivan, y los incidentes conservan la del momento en que se
          reportaron.
        </p>
      </div>
      <v-btn
        color="primary"
        variant="flat"
        class="entra"
        style="--i: 2"
        :prepend-icon="iconos.crear"
        @click="abrir(null)"
      >
        Nueva empresa
      </v-btn>
    </header>

    <div class="barra-filtros entra" style="--i: 3">
      <CampoDeBusqueda
        :model-value="criterio.texto ?? ''"
        etiqueta="Buscar por nombre"
        @update:model-value="cambiar({ q: $event })"
      />
    </div>

    <MensajeEnPagina
      v-if="lista.isError.value"
      tipo="error"
      titulo="No se pudo cargar la lista"
      reintentable
      class="mb-3"
      @reintentar="lista.refetch()"
    >
      {{ lista.error.value?.message }}
    </MensajeEnPagina>

    <div class="tarjeta entra" style="--i: 4">
      <div v-if="lista.data.value && !lista.data.value.elementos.length" class="empresas__vacio">
        <EstadoVacio
          :icono="iconos.empresa"
          :titulo="criterio.texto ? 'Ninguna empresa coincide' : 'Todavía no hay empresas'"
        />
      </div>
      <v-table v-else density="compact">
        <thead>
          <tr>
            <th>Empresa</th>
            <th>Personas</th>
            <th>Estado</th>
            <th class="columna-acciones"><span class="solo-lectores">Acciones</span></th>
          </tr>
        </thead>
        <tbody v-if="!lista.data.value">
          <tr v-for="fila in 5" :key="fila">
            <td><EsqueletoDeCarga :ancho="`${150 + fila * 14}px`" /></td>
            <td><EsqueletoDeCarga ancho="30px" /></td>
            <td><EsqueletoDeCarga ancho="60px" /></td>
            <td />
          </tr>
        </tbody>
        <TransitionGroup v-else name="fila" tag="tbody">
          <tr v-for="empresa in lista.data.value.elementos" :key="empresa.id">
            <td class="empresa">
              <v-icon :icon="iconos.empresa" size="18" class="empresa__icono" />
              <button type="button" class="empresa__nombre" @click="abrir(empresa)">{{ empresa.nombre }}</button>
            </td>
            <td class="num">{{ empresa.usuarios }}</td>
            <td class="text-no-wrap">
              <span class="punto" :class="empresa.activo ? 'punto--activo' : 'punto--inactivo'" aria-hidden="true" />
              {{ empresa.activo ? 'Activa' : 'Inactiva' }}
            </td>
            <td class="columna-acciones">
              <v-menu location="bottom end">
                <template #activator="{ props: activador }">
                  <v-btn
                    v-bind="activador"
                    :icon="iconos.masAcciones"
                    variant="text"
                    size="x-small"
                    :aria-label="`Acciones de ${empresa.nombre}`"
                  />
                </template>
                <v-list min-width="200">
                  <v-list-item :prepend-icon="iconos.editar" title="Renombrar" @click="abrir(empresa)" />
                  <v-list-item
                    :prepend-icon="empresa.activo ? iconos.usuarioDesactivado : iconos.usuarioReactivado"
                    :title="empresa.activo ? 'Desactivar' : 'Reactivar'"
                    :base-color="empresa.activo ? 'error' : undefined"
                    @click="cambiarActiva(empresa)"
                  />
                </v-list>
              </v-menu>
            </td>
          </tr>
        </TransitionGroup>
      </v-table>
      <PieDeTabla
        v-if="lista.data.value && lista.data.value.total > 0"
        :pagina="lista.data.value.pagina"
        :ultima-pagina="lista.data.value.ultimaPagina"
        :por-pagina="15"
        :total="lista.data.value.total"
        :en-pagina="lista.data.value.elementos.length"
        :tamanos="[15]"
        @update:pagina="cambiar({ pagina: $event })"
      />
    </div>

    <DialogoTexto
      v-model="dialogoAbierto"
      :titulo="creando ? 'Nueva empresa' : `Renombrar ${editada?.nombre ?? ''}`"
      etiqueta="Nombre de la empresa"
      :accion="creando ? 'Crear empresa' : 'Renombrar'"
      :inicial="editada?.nombre ?? ''"
      :maximo="MAXIMO_NOMBRE"
      :pendiente="pendiente"
      :error="errorDelDialogo"
      @confirmar="confirmarNombre"
    />
  </div>
</template>

<style scoped>
.empresa {
  display: flex;
  gap: 10px;
  align-items: center;
  height: 44px;
}

.empresa__icono {
  color: var(--suave);
}

.empresa__nombre {
  padding: 0;
  color: var(--texto);
  font: inherit;
  font-weight: 500;
  background: none;
  border: 0;
  cursor: pointer;
}

.empresa__nombre:hover {
  color: var(--acento);
}

.punto {
  margin-right: 6px;
}

.empresas__vacio {
  padding: 16px;
}
</style>
