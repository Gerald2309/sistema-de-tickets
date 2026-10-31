<script setup>
import { computed, ref } from 'vue'
import AvatarPersona from '@/compartido/componentes/AvatarPersona.vue'
import CampoDeBusqueda from '@/compartido/componentes/CampoDeBusqueda.vue'
import ChipsDeFiltros from '@/compartido/componentes/ChipsDeFiltros.vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import MenuFiltro from '@/compartido/componentes/MenuFiltro.vue'
import PieDeTabla from '@/compartido/componentes/PieDeTabla.vue'
import { iconos } from '@/compartido/iconos.js'
import DialogoDesactivar from '../componentes/DialogoDesactivar.vue'
import DialogoRestablecer from '../componentes/DialogoRestablecer.vue'
import PanelUsuario from '../componentes/PanelUsuario.vue'
import { useEmpresas } from '../composables/usePersonas.js'
import { useEdicionDeUsuarios, useListadoUsuarios } from '../composables/useUsuarios.js'

/** @typedef {import('../tipos.js').Usuario} Usuario */

const { filtros, lista, conteos, roles, cambiar } = useListadoUsuarios()
const { empresas, nombreDe: nombreDeEmpresa } = useEmpresas()
const { alternarActivo } = useEdicionDeUsuarios()

const vistas = computed(() => [
  { valor: 'todos', etiqueta: 'Todos', conteo: conteos.todos.data.value },
  { valor: 'activos', etiqueta: 'Activos', conteo: conteos.activos.data.value },
  { valor: 'inactivos', etiqueta: 'Inactivos', conteo: conteos.inactivos.data.value },
])
const opcionesDeRol = computed(() =>
  (roles.data.value ?? []).map((rol) => ({ valor: String(rol.id), nombre: rol.nombre })),
)
const opcionesDeEmpresa = computed(() =>
  empresas.value.map((empresa) => ({ valor: String(empresa.id), nombre: empresa.nombre })),
)
const chips = computed(() => [
  ...(filtros.value.rol
    ? [
        {
          clave: 'rol',
          etiqueta: `Rol: ${roles.data.value?.find((rol) => rol.id === filtros.value.rol)?.nombre ?? '…'}`,
        },
      ]
    : []),
  ...(filtros.value.cliente
    ? [{ clave: 'cliente', etiqueta: `Empresa: ${nombreDeEmpresa(filtros.value.cliente) ?? '…'}` }]
    : []),
])
const hayFiltros = computed(() =>
  Boolean(filtros.value.q || filtros.value.rol || filtros.value.cliente || filtros.value.estado),
)

const panelAbierto = ref(false)
const restablecerAbierto = ref(false)
const elegido = ref(/** @type {Usuario | null} */ (null))

/** @param {Usuario | null} usuario */
function editar(usuario) {
  elegido.value = usuario
  panelAbierto.value = true
}

/** @param {Usuario} usuario */
function restablecer(usuario) {
  elegido.value = usuario
  restablecerAbierto.value = true
}

// Desactivar pregunta antes qué pasa con sus incidentes sin resolver; reactivar es directo.
const desactivando = ref(/** @type {Usuario | null} */ (null))
const enviandoDesactivacion = ref(false)
const dialogoDesactivar = computed({
  get: () => desactivando.value !== null,
  set: (abierto) => !abierto && (desactivando.value = null),
})

/** @param {Usuario} usuario */
async function cambiarActivo(usuario) {
  if (usuario.activo) {
    desactivando.value = usuario
    return
  }
  await alternarActivo(usuario)
}

/** @param {number | null} destinoId a quién pasan sus incidentes sin resolver; null: a la cola */
async function confirmarDesactivacion(destinoId) {
  if (!desactivando.value) {
    return
  }
  enviandoDesactivacion.value = true
  await alternarActivo(desactivando.value, destinoId)
  enviandoDesactivacion.value = false
  desactivando.value = null
}

/** @param {string} clave */
const quitar = (clave) => cambiar({ [clave]: undefined })
const limpiar = () => cambiar({ q: undefined, rol: undefined, cliente: undefined, estado: undefined })
</script>

<template>
  <div class="pagina">
    <div class="migas entra">Administración</div>
    <header class="cabecera">
      <h1 v-revelar class="titulo-pagina">Usuarios</h1>
      <v-btn
        color="primary"
        variant="flat"
        class="entra"
        style="--i: 2"
        :prepend-icon="iconos.usuarioCreado"
        @click="editar(null)"
      >
        Nuevo usuario
      </v-btn>
    </header>

    <div class="barra-filtros entra" style="--i: 3">
      <ControlSegmentado
        :model-value="filtros.estado ?? 'todos'"
        :opciones="vistas"
        etiqueta="Estado de las cuentas"
        @update:model-value="cambiar({ estado: $event === 'todos' ? undefined : $event })"
      />
      <CampoDeBusqueda
        :model-value="filtros.q ?? ''"
        etiqueta="Buscar por nombre o correo"
        @update:model-value="cambiar({ q: $event || undefined })"
      />
      <MenuFiltro
        :model-value="filtros.rol ? String(filtros.rol) : undefined"
        etiqueta="Rol"
        :opciones="opcionesDeRol"
        @update:model-value="cambiar({ rol: Number($event) })"
      />
      <MenuFiltro
        v-if="opcionesDeEmpresa.length"
        :model-value="filtros.cliente ? String(filtros.cliente) : undefined"
        etiqueta="Empresa"
        :opciones="opcionesDeEmpresa"
        @update:model-value="cambiar({ cliente: Number($event) })"
      />
      <ChipsDeFiltros :filtros="chips" @quitar="quitar" @limpiar="limpiar" />
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

    <div class="tarjeta entra" style="--i: 5">
      <div v-if="lista.data.value && !lista.data.value.elementos.length" class="usuarios__vacio">
        <EstadoVacio :icono="iconos.vacio" titulo="Nadie coincide con la búsqueda">
          <v-btn v-if="hayFiltros" size="small" variant="outlined" @click="limpiar">Limpiar filtros</v-btn>
        </EstadoVacio>
      </div>
      <v-table v-else v-desplazable="'Usuarios'" density="compact">
        <thead>
          <tr>
            <th>Persona</th>
            <th>Rol</th>
            <th>Empresa</th>
            <th>Estado</th>
            <th class="columna-acciones"><span class="solo-lectores">Acciones</span></th>
          </tr>
        </thead>
        <tbody v-if="!lista.data.value">
          <tr v-for="fila in 6" :key="fila">
            <td><EsqueletoDeCarga :ancho="`${140 + fila * 12}px`" /></td>
            <td><EsqueletoDeCarga ancho="90px" /></td>
            <td><EsqueletoDeCarga ancho="110px" /></td>
            <td><EsqueletoDeCarga ancho="60px" /></td>
            <td />
          </tr>
        </tbody>
        <TransitionGroup v-else name="fila" tag="tbody">
          <tr v-for="usuario in lista.data.value.elementos" :key="usuario.id">
            <td>
              <div class="persona">
                <AvatarPersona :nombre="usuario.nombre" :tamano="30" :con-nombre="false" />
                <div>
                  <button type="button" class="persona__nombre" @click="editar(usuario)">{{ usuario.nombre }}</button>
                  <div class="nota">{{ usuario.correo }}</div>
                </div>
              </div>
            </td>
            <td class="text-no-wrap">{{ usuario.rol.nombre }}</td>
            <td class="text-no-wrap">
              <span v-if="usuario.cliente">{{ usuario.cliente.nombre }}</span>
              <span v-else class="tenue">Personal de Psicol</span>
            </td>
            <td class="text-no-wrap">
              <span class="punto" :class="usuario.activo ? 'punto--activo' : 'punto--inactivo'" aria-hidden="true" />
              {{ usuario.activo ? 'Activo' : 'Inactivo' }}
            </td>
            <td class="columna-acciones">
              <v-menu location="bottom end">
                <template #activator="{ props: activador }">
                  <v-btn
                    v-bind="activador"
                    :icon="iconos.masAcciones"
                    variant="text"
                    size="x-small"
                    :aria-label="`Acciones de ${usuario.nombre}`"
                  />
                </template>
                <v-list min-width="220">
                  <v-list-item :prepend-icon="iconos.editar" title="Editar" @click="editar(usuario)" />
                  <v-list-item
                    :prepend-icon="iconos.contrasenaRestablecida"
                    title="Restablecer contraseña"
                    @click="restablecer(usuario)"
                  />
                  <v-list-item
                    v-if="usuario.activo"
                    :prepend-icon="iconos.usuarioDesactivado"
                    title="Desactivar"
                    base-color="error"
                    @click="cambiarActivo(usuario)"
                  />
                  <v-list-item
                    v-else
                    :prepend-icon="iconos.usuarioReactivado"
                    title="Reactivar"
                    @click="cambiarActivo(usuario)"
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

    <PanelUsuario v-model="panelAbierto" :usuario="elegido" />
    <DialogoDesactivar
      v-model="dialogoDesactivar"
      :usuario="desactivando"
      :pendiente="enviandoDesactivacion"
      @confirmar="confirmarDesactivacion"
    />
    <DialogoRestablecer v-model="restablecerAbierto" :usuario="elegido" />
  </div>
</template>

<style scoped>
.barra-filtros > :first-child {
  margin-right: 8px;
}

.persona {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 6px 0;
}

.persona__nombre {
  padding: 0;
  color: var(--texto);
  font: inherit;
  font-weight: 500;
  background: none;
  border: 0;
  cursor: pointer;
}

.persona__nombre:hover {
  color: var(--acento);
}

.punto {
  margin-right: 6px;
}

.usuarios__vacio {
  padding: 16px;
}
</style>
