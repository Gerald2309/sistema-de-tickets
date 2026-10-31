<script setup>
import { computed, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import DialogoTexto from '@/compartido/componentes/DialogoTexto.vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { iconos } from '@/compartido/iconos.js'
import { useRolesAdmin } from '../composables/useRolesAdmin.js'
import { GRUPOS_DE_PERMISOS } from '../presentacion.js'

const admin = useRolesAdmin()
const { roles, rol, borrador, cambios } = admin
const confirmacion = useConfirmacion()

const MAXIMO_NOMBRE = 100
const dialogo = ref(/** @type {'crear' | 'renombrar' | null} */ (null))
const errorDelDialogo = ref('')
const errorDePermisos = ref('')

const dialogoAbierto = computed({
  get: () => dialogo.value !== null,
  set: (valor) => {
    if (!valor) {
      dialogo.value = null
    }
  },
})

/** @param {'crear' | 'renombrar'} tipo */
function abrirDialogo(tipo) {
  errorDelDialogo.value = ''
  dialogo.value = tipo
}

/** @param {string} nombre */
async function confirmarNombre(nombre) {
  errorDelDialogo.value = await (dialogo.value === 'crear' ? admin.crear(nombre) : admin.renombrar(nombre))
  if (!errorDelDialogo.value) {
    dialogo.value = null
  }
}

/** Con cambios sin guardar, cambiar de rol o salir pide confirmación (RF-F39). */
async function puedeDescartar() {
  if (cambios.value === 0) {
    return true
  }
  return confirmacion.pedir({
    titulo: 'Hay permisos sin guardar',
    texto: `Si continúa, se pierden ${cambios.value === 1 ? 'el cambio' : `los ${cambios.value} cambios`} de ${rol.value?.nombre}.`,
    accion: 'Descartar cambios',
    peligrosa: true,
  })
}

/** @param {number} id */
async function elegir(id) {
  if (id !== rol.value?.id && (await puedeDescartar())) {
    admin.descartar()
    admin.elegir(id)
  }
}

onBeforeRouteLeave(async (destino) => destino.name === 'roles' || (await puedeDescartar()))

async function guardar() {
  errorDePermisos.value = await admin.guardarPermisos()
}

async function eliminar() {
  if (!rol.value) {
    return
  }
  const confirmado = await confirmacion.pedir({
    titulo: `¿Eliminar el rol ${rol.value.nombre}?`,
    texto: 'No tiene personas asignadas. Esta acción no se puede deshacer.',
    accion: 'Eliminar',
    peligrosa: true,
  })
  if (confirmado) {
    errorDePermisos.value = await admin.eliminar()
  }
}

const personas = (/** @type {number} */ n) => `${n} ${n === 1 ? 'persona' : 'personas'}`
</script>

<template>
  <div class="pagina">
    <div class="migas entra">Administración</div>
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Roles y permisos</h1>
        <p class="subtitulo entra" style="--i: 2">
          Lo que puede hacer cada rol. Los cambios se aplican de inmediato a todas sus personas.
        </p>
      </div>
      <v-btn
        variant="outlined"
        class="entra"
        style="--i: 2"
        :prepend-icon="iconos.crear"
        @click="abrirDialogo('crear')"
      >
        Nuevo rol
      </v-btn>
    </header>

    <MensajeEnPagina
      v-if="roles.isError.value"
      tipo="error"
      titulo="No se pudieron cargar los roles"
      reintentable
      @reintentar="roles.refetch()"
    >
      {{ roles.error.value?.message }}
    </MensajeEnPagina>

    <div v-else class="roles">
      <section class="tarjeta entra" style="--i: 3" aria-labelledby="titulo-roles">
        <header class="tarjeta__cabecera">
          <h2 id="titulo-roles" class="tarjeta__titulo">Roles</h2>
          <span class="nota">{{ roles.data.value?.length ?? '' }}</span>
        </header>
        <div v-if="!roles.data.value" class="tarjeta__cuerpo roles__cargando">
          <EsqueletoDeCarga v-for="n in 5" :key="n" alto="36px" />
        </div>
        <nav v-else aria-label="Roles">
          <button
            v-for="candidato in roles.data.value"
            :key="candidato.id"
            type="button"
            class="rol-item"
            :class="{ 'rol-item--activo': candidato.id === rol?.id }"
            :aria-current="candidato.id === rol?.id ? 'true' : undefined"
            @click="elegir(candidato.id)"
          >
            <span>
              <b>{{ candidato.nombre }}</b>
              <span class="nota d-block"
                >{{ personas(candidato.usuarios) }} · {{ candidato.permisos.length }} permisos</span
              >
            </span>
            <v-icon :icon="iconos.siguiente" size="18" />
          </button>
        </nav>
      </section>

      <section v-if="rol" class="tarjeta entra" style="--i: 4" aria-labelledby="titulo-rol">
        <header class="tarjeta__cabecera">
          <h2 id="titulo-rol" class="tarjeta__titulo">{{ rol.nombre }}</h2>
          <div class="roles__acciones">
            <v-btn size="small" variant="text" :prepend-icon="iconos.editar" @click="abrirDialogo('renombrar')"
              >Renombrar</v-btn
            >
            <v-tooltip
              :disabled="rol.usuarios === 0"
              location="top"
              :text="`No se puede eliminar: tiene ${personas(rol.usuarios)} asignadas`"
            >
              <template #activator="{ props: activador }">
                <span v-bind="activador">
                  <v-btn
                    size="small"
                    variant="text"
                    color="error"
                    :prepend-icon="iconos.eliminar"
                    :disabled="rol.usuarios > 0"
                    :loading="admin.pendiente.eliminacion.value"
                    @click="eliminar"
                  >
                    Eliminar
                  </v-btn>
                </span>
              </template>
            </v-tooltip>
          </div>
        </header>

        <div class="plegable" :class="{ 'plegable--abierta': cambios > 0 }" :inert="cambios === 0 || undefined">
          <div>
            <div class="guardar-barra" role="status">
              <b>{{ cambios }} {{ cambios === 1 ? 'cambio' : 'cambios' }} sin guardar</b>
              <v-spacer />
              <v-btn size="small" variant="text" @click="admin.descartar">Descartar</v-btn>
              <BotonEnvio
                tipo="button"
                tamano="small"
                texto="Guardar permisos"
                :pendiente="admin.pendiente.permisos.value"
                @click="guardar"
              />
            </div>
          </div>
        </div>

        <MensajeEnPagina v-if="errorDePermisos" tipo="error" titulo="No se pudo guardar" class="ma-4">{{
          errorDePermisos
        }}</MensajeEnPagina>

        <div
          v-for="grupo in GRUPOS_DE_PERMISOS"
          :key="grupo.titulo"
          role="group"
          :aria-labelledby="`grupo-${grupo.titulo}`"
        >
          <div :id="`grupo-${grupo.titulo}`" class="permiso-grupo">{{ grupo.titulo }}</div>
          <label v-for="permiso in grupo.permisos" :key="permiso.clave" class="permiso">
            <span>
              <b>{{ permiso.nombre }}</b>
              <span class="nota d-block">{{ permiso.descripcion }}</span>
            </span>
            <InterruptorCompacto
              :model-value="borrador.has(permiso.clave)"
              :etiqueta="permiso.nombre"
              @update:model-value="admin.alternar(permiso.clave, $event)"
            />
          </label>
        </div>
      </section>
    </div>

    <DialogoTexto
      v-model="dialogoAbierto"
      :titulo="dialogo === 'crear' ? 'Nuevo rol' : `Renombrar ${rol?.nombre ?? ''}`"
      etiqueta="Nombre del rol"
      :accion="dialogo === 'crear' ? 'Crear rol' : 'Renombrar'"
      :inicial="dialogo === 'renombrar' ? (rol?.nombre ?? '') : ''"
      :maximo="MAXIMO_NOMBRE"
      :pendiente="admin.pendiente.nombre.value"
      :error="errorDelDialogo"
      @confirmar="confirmarNombre"
    />
  </div>
</template>

<style scoped>
.roles {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  gap: 12px;
  align-items: start;
}

.roles__cargando {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.roles__acciones {
  display: flex;
  gap: 4px;
}

.rol-item {
  position: relative;
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  color: var(--texto);
  font: inherit;
  text-align: left;
  background: none;
  border: 0;
  border-bottom: 1px solid var(--borde);
  cursor: pointer;
  transition: background-color var(--rapida);
}

.rol-item:last-child {
  border-bottom: 0;
}

.rol-item:hover {
  background: var(--sutil);
}

.rol-item b {
  font-size: 14px;
  font-weight: 600;
}

.rol-item .v-icon {
  color: var(--tenue);
}

.rol-item--activo,
.rol-item--activo:hover {
  background: var(--acento-fondo);
}

.rol-item--activo::before {
  position: absolute;
  top: 8px;
  bottom: 8px;
  left: 0;
  width: 3px;
  background: var(--marca-cian);
  border-radius: 0 2px 2px 0;
  content: '';
}

.guardar-barra {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 8px 16px;
  color: var(--acento-texto);
  font-size: 13.5px;
  background: var(--acento-fondo);
  border-bottom: 1px solid var(--acento-borde);
}

.permiso-grupo {
  padding: 16px 18px 6px;
  color: var(--suave);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.permiso {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
  padding: 10px 18px;
  border-bottom: 1px solid var(--borde);
  cursor: pointer;
  transition: background-color var(--rapida);
}

.permiso:hover {
  background: var(--sutil);
}

.permiso b {
  font-size: 14px;
  font-weight: 500;
}

@media (width <= 960px) {
  .roles {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
