<script setup>
import { computed, ref, watch } from 'vue'
import { MAXIMO_DESCRIPCION, MAXIMO_TITULO } from '../composables/useFormularioIncidente.js'

/**
 * Corregir el título y la descripción: lo hace quien reportó el incidente mientras nadie lo ha tomado. Solo
 * se envía lo que cambió; el cambio queda en el historial.
 */
const props = defineProps({
  titulo: { type: String, required: true },
  descripcion: { type: String, required: true },
  pendiente: { type: Boolean, default: false },
})
const abierto = defineModel({ type: Boolean, default: false })
const emit = defineEmits(['confirmar'])

const campos = ref({ titulo: '', descripcion: '' })
watch(abierto, (valor) => valor && (campos.value = { titulo: props.titulo, descripcion: props.descripcion }))

const cambios = computed(() => {
  const titulo = campos.value.titulo.trim()
  const descripcion = campos.value.descripcion.trim()
  return {
    ...(titulo !== props.titulo ? { titulo } : {}),
    ...(descripcion !== props.descripcion ? { descripcion } : {}),
  }
})
const valido = computed(
  () =>
    Object.keys(cambios.value).length > 0 &&
    campos.value.titulo.trim().length > 0 &&
    campos.value.descripcion.trim().length > 0,
)
</script>

<template>
  <v-dialog v-model="abierto" max-width="620">
    <v-card class="dialogo-relato">
      <form novalidate @submit.prevent="valido && emit('confirmar', cambios)">
        <v-card-title class="dialogo-relato__titulo">Corregir lo que reportó</v-card-title>
        <v-card-text>
          <p class="nota dialogo-relato__ayuda">
            Puede corregirlo mientras nadie de soporte lo haya tomado. El cambio queda en el historial.
          </p>
          <v-text-field
            v-model="campos.titulo"
            label="Título *"
            :counter="MAXIMO_TITULO"
            :maxlength="MAXIMO_TITULO"
            autofocus
          />
          <v-textarea
            v-model="campos.descripcion"
            label="Descripción *"
            :counter="MAXIMO_DESCRIPCION"
            :maxlength="MAXIMO_DESCRIPCION"
            rows="6"
            auto-grow
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
          <v-btn type="submit" color="primary" variant="flat" :disabled="!valido" :loading="pendiente">Guardar</v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.dialogo-relato__titulo {
  padding-top: 20px;
  font-weight: 700;
}

.dialogo-relato__ayuda {
  margin-bottom: 14px;
}
</style>
