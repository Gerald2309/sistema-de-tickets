<script setup>
import { computed, ref, watch } from 'vue'
import { LONGITUD_MAXIMA, LONGITUD_MINIMA } from '../composables/useCambioDeContrasena.js'
import { useEdicionDeUsuarios } from '../composables/useUsuarios.js'

/** Restablecer la contraseña de otra persona (RF-F42): se cierran todas sus sesiones. */
const props = defineProps({
  usuario: {
    type: /** @type {import('vue').PropType<import('../tipos.js').Usuario | null>} */ (Object),
    default: null,
  },
})
const abierto = defineModel({ type: Boolean, default: false })
const { restablecer: restablecerContrasena, restableciendo } = useEdicionDeUsuarios()

const contrasena = ref('')
const error = ref('')
watch(abierto, () => {
  contrasena.value = ''
  error.value = ''
})
const valida = computed(() => contrasena.value.length >= LONGITUD_MINIMA)

async function restablecer() {
  if (!props.usuario || !valida.value) {
    return
  }
  error.value = await restablecerContrasena(props.usuario, contrasena.value)
  if (!error.value) {
    abierto.value = false
  }
}
</script>

<template>
  <v-dialog v-model="abierto" max-width="460">
    <v-card v-if="usuario" class="restablecer">
      <form novalidate @submit.prevent="restablecer">
        <v-card-title class="restablecer__titulo">Restablecer la contraseña de {{ usuario.nombre }}</v-card-title>
        <v-card-text>
          <p class="restablecer__ayuda">
            Comuníquele la nueva contraseña por un canal seguro. Se cerrarán todas sus sesiones.
          </p>
          <v-text-field
            v-model="contrasena"
            label="Nueva contraseña *"
            type="password"
            autocomplete="new-password"
            :counter="LONGITUD_MAXIMA"
            :maxlength="LONGITUD_MAXIMA"
            :hint="`Mínimo ${LONGITUD_MINIMA} caracteres.`"
            persistent-hint
            :error-messages="error ? [error] : []"
            autofocus
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
          <v-btn type="submit" color="primary" variant="flat" :disabled="!valida" :loading="restableciendo">
            Restablecer
          </v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.restablecer {
  padding: 8px;
  border: 1px solid var(--borde);
}

.restablecer__titulo {
  font-size: 17px;
  font-weight: 700;
  white-space: normal;
}

.restablecer__ayuda {
  margin: 0 0 14px;
  color: var(--suave);
}
</style>
