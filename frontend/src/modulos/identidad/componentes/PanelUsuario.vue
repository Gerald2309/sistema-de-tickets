<script setup>
import { computed, reactive, ref, watch } from 'vue'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import PanelLateral from '@/compartido/componentes/PanelLateral.vue'
import { useErroresDeFormulario } from '@/compartido/composables/useErroresDeFormulario.js'
import { LONGITUD_MAXIMA, cumpleRequisitos } from '../contrasena.js'
import { useEmpresas } from '../composables/usePersonas.js'
import { useEdicionDeUsuarios, useRoles } from '../composables/useUsuarios.js'
import RequisitosDeContrasena from './RequisitosDeContrasena.vue'

/**
 * Alta y edición de un usuario en panel lateral (RF-F42, sistema de diseño). La contraseña inicial solo se pide
 * al crear; después se restablece desde el menú de la fila.
 */
const props = defineProps({
  usuario: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Usuario | null>} */ (Object),
    default: null,
  },
})
const abierto = defineModel({ type: Boolean, default: false })

const roles = useRoles()
const { empresas } = useEmpresas()
const { guardado } = useEdicionDeUsuarios()
const servidor = useErroresDeFormulario({
  name: 'nombre',
  email: 'correo',
  password: 'contrasena',
  rol_id: 'rolId',
  cliente_id: 'clienteId',
  activo: 'activo',
})

const PERSONAL_DE_PSICOL = 0
/** Al crear, el rol propuesto es Solicitante (el más común); los roles pueden llegar después de abrir el panel. */
const rolPropuesto = () => roles.data.value?.find((rol) => rol.nombre === 'Solicitante')?.id ?? 0
const datos = reactive({
  nombre: '',
  correo: '',
  rolId: 0,
  clienteId: PERSONAL_DE_PSICOL,
  contrasena: '',
  activo: true,
})
const contrasenaTocada = ref(false)

watch(abierto, (valor) => {
  if (!valor) {
    return
  }
  servidor.limpiar()
  contrasenaTocada.value = false
  Object.assign(datos, {
    nombre: props.usuario?.nombre ?? '',
    correo: props.usuario?.correo ?? '',
    rolId: props.usuario?.rol.id ?? rolPropuesto(),
    clienteId: props.usuario?.cliente?.id ?? PERSONAL_DE_PSICOL,
    contrasena: '',
    activo: props.usuario?.activo ?? true,
  })
})
watch(
  () => roles.data.value,
  () => abierto.value && datos.rolId === 0 && (datos.rolId = rolPropuesto()),
)

const opcionesDeEmpresa = computed(() => [
  { id: PERSONAL_DE_PSICOL, nombre: 'Personal de Psicol' },
  // Una empresa ya desactivada sigue visible para quien la tiene asignada.
  ...(props.usuario?.cliente && !empresas.value.some((empresa) => empresa.id === props.usuario?.cliente?.id)
    ? [props.usuario.cliente]
    : []),
  ...empresas.value,
])

const completo = computed(
  () =>
    datos.nombre.trim() !== '' &&
    datos.correo.trim() !== '' &&
    datos.rolId > 0 &&
    (props.usuario !== null || cumpleRequisitos(datos.contrasena)),
)

/** Al salir del campo la marca en rojo: el botón deshabilitado por sí solo no explica por qué. */
const errorDeContrasena = computed(() =>
  contrasenaTocada.value && !cumpleRequisitos(datos.contrasena)
    ? ['Aún no cumple todos los requisitos.']
    : servidor.errores.contrasena,
)

async function guardar() {
  if (!completo.value) {
    return
  }
  servidor.limpiar()
  try {
    await guardado.mutateAsync({
      original: props.usuario,
      datos: {
        ...datos,
        nombre: datos.nombre.trim(),
        correo: datos.correo.trim().toLowerCase(),
        clienteId: datos.clienteId === PERSONAL_DE_PSICOL ? null : datos.clienteId,
      },
    })
    abierto.value = false
  } catch (causa) {
    servidor.aplicar(causa)
  }
}
</script>

<template>
  <PanelLateral v-model="abierto" :titulo="usuario ? 'Editar usuario' : 'Nuevo usuario'">
    <form id="formulario-usuario" class="panel-usuario" novalidate @submit.prevent="guardar">
      <MensajeEnPagina v-if="servidor.general.value" tipo="error" titulo="No se pudo guardar" class="mb-4">
        {{ servidor.general.value }}
      </MensajeEnPagina>
      <v-text-field
        v-model="datos.nombre"
        label="Nombre *"
        maxlength="255"
        autocomplete="off"
        :error-messages="servidor.errores.nombre"
        @update:model-value="servidor.limpiarCampo('nombre')"
      />
      <v-text-field
        v-model="datos.correo"
        label="Correo *"
        type="email"
        maxlength="255"
        autocomplete="off"
        hint="Se guarda en minúsculas."
        persistent-hint
        :error-messages="servidor.errores.correo"
        @update:model-value="servidor.limpiarCampo('correo')"
      />
      <v-select
        v-model="datos.rolId"
        label="Rol *"
        :items="roles.data.value ?? []"
        item-title="nombre"
        item-value="id"
        :loading="roles.isPending.value"
        :error-messages="servidor.errores.rolId"
        @update:model-value="servidor.limpiarCampo('rolId')"
      />
      <v-autocomplete
        v-model="datos.clienteId"
        label="Empresa"
        :items="opcionesDeEmpresa"
        item-title="nombre"
        item-value="id"
        hint="Los incidentes guardan la empresa del momento en que se crean."
        persistent-hint
        :error-messages="servidor.errores.clienteId"
        @update:model-value="servidor.limpiarCampo('clienteId')"
      />
      <v-text-field
        v-if="!usuario"
        v-model="datos.contrasena"
        label="Contraseña inicial *"
        type="password"
        autocomplete="new-password"
        :counter="LONGITUD_MAXIMA"
        :maxlength="LONGITUD_MAXIMA"
        hint="La persona puede cambiarla en Mi cuenta."
        persistent-hint
        :error-messages="errorDeContrasena"
        @update:model-value="servidor.limpiarCampo('contrasena')"
        @blur="contrasenaTocada = true"
      />
      <RequisitosDeContrasena v-if="!usuario" :contrasena="datos.contrasena" />
      <label v-else class="panel-usuario__activo">
        <b>Puede iniciar sesión</b>
        <InterruptorCompacto v-model="datos.activo" etiqueta="Puede iniciar sesión" />
      </label>
      <p v-if="usuario?.activo && !datos.activo" class="nota">
        Al guardar, sus incidentes sin resolver volverán a la cola sin asignar.
      </p>
      <p v-if="servidor.errores.activo" class="panel-usuario__error">{{ servidor.errores.activo[0] }}</p>
    </form>
    <template #pie>
      <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
      <BotonEnvio
        form="formulario-usuario"
        :texto="usuario ? 'Guardar cambios' : 'Crear usuario'"
        :pendiente="guardado.isPending.value"
        :deshabilitado="!completo"
      />
    </template>
  </PanelLateral>
</template>

<style scoped>
.panel-usuario {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.panel-usuario__activo {
  display: flex;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
  padding: 10px 0;
  cursor: pointer;
}

.panel-usuario__activo b {
  font-weight: 500;
}

.panel-usuario__error {
  margin: 0;
  color: var(--error);
  font-size: 12px;
}
</style>
