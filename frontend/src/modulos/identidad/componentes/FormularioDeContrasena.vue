<script setup>
import { ref } from 'vue'
import BotonEnvio from '@/compartido/componentes/BotonEnvio.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { iconos } from '@/compartido/iconos.js'
import { useCambioDeContrasena } from '../composables/useCambioDeContrasena.js'
import { LONGITUD_MAXIMA } from '../contrasena.js'
import RequisitosDeContrasena from './RequisitosDeContrasena.vue'

/** Cambio de la propia contraseña: en Mi cuenta y en el cambio obligatorio tras recibirla de un administrador. */
const emit = defineEmits({ cambiada: () => true })

const { actual, nueva, repetida, pendiente, exito, errores, general, coincide, listo, guardar, limpiarCampo } =
  useCambioDeContrasena({ alCambiar: () => emit('cambiada') })
const verNueva = ref(false)
</script>

<template>
  <form novalidate @submit.prevent="guardar">
    <MensajeEnPagina v-if="general" tipo="error" titulo="No se pudo cambiar la contraseña" class="mb-4">
      {{ general }}
    </MensajeEnPagina>
    <v-text-field
      v-model="actual"
      label="Contraseña actual *"
      type="password"
      autocomplete="current-password"
      :error-messages="errores.actual"
      @update:model-value="limpiarCampo('actual')"
    />
    <v-text-field
      v-model="nueva"
      label="Nueva contraseña *"
      :type="verNueva ? 'text' : 'password'"
      autocomplete="new-password"
      :counter="LONGITUD_MAXIMA"
      :maxlength="LONGITUD_MAXIMA"
      :error-messages="errores.nueva"
      aria-describedby="requisito-contrasena"
      @update:model-value="limpiarCampo('nueva')"
    >
      <template #append-inner>
        <v-btn
          :icon="verNueva ? iconos.ocultar : iconos.ver"
          variant="text"
          size="small"
          :aria-label="verNueva ? 'Ocultar la contraseña' : 'Mostrar la contraseña'"
          :aria-pressed="verNueva"
          @click="verNueva = !verNueva"
        />
      </template>
    </v-text-field>
    <RequisitosDeContrasena id="requisito-contrasena" :contrasena="nueva" class="formulario-contrasena__requisitos" />
    <v-text-field
      v-model="repetida"
      label="Repita la nueva contraseña *"
      type="password"
      autocomplete="new-password"
      :error-messages="coincide ? [] : ['No coincide con la nueva contraseña.']"
    />
    <div class="formulario-por-secciones__pie">
      <BotonEnvio texto="Cambiar contraseña" :pendiente="pendiente" :exito="exito" :deshabilitado="!listo" />
    </div>
  </form>
</template>

<style scoped>
.formulario-contrasena__requisitos {
  margin: -6px 0 14px;
}
</style>
