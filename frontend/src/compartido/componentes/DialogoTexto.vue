<script setup>
import { computed, ref, watch } from 'vue'

/**
 * Diálogo de un solo campo (crear o renombrar un rol o una empresa). Quien lo usa envía y, si la API responde
 * con un error, lo pasa en `error`: el texto escrito se conserva.
 */
const props = defineProps({
  titulo: { type: String, required: true },
  etiqueta: { type: String, required: true },
  accion: { type: String, required: true },
  inicial: { type: String, default: '' },
  maximo: { type: Number, required: true },
  pendiente: { type: Boolean, default: false },
  error: { type: String, default: '' },
})
const abierto = defineModel({ type: Boolean, default: false })
const emit = defineEmits(['confirmar'])

const texto = ref('')
watch(abierto, (valor) => valor && (texto.value = props.inicial))
const valido = computed(() => texto.value.trim().length > 0 && texto.value.trim() !== props.inicial)
</script>

<template>
  <v-dialog v-model="abierto" max-width="460">
    <v-card class="dialogo-texto">
      <form novalidate @submit.prevent="valido && emit('confirmar', texto.trim())">
        <v-card-title class="dialogo-texto__titulo">{{ titulo }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="texto"
            :label="`${etiqueta} *`"
            :counter="maximo"
            :maxlength="maximo"
            :error-messages="error ? [error] : []"
            autofocus
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="abierto = false">Cancelar</v-btn>
          <v-btn type="submit" color="primary" variant="flat" :disabled="!valido" :loading="pendiente">{{
            accion
          }}</v-btn>
        </v-card-actions>
      </form>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.dialogo-texto {
  padding: 8px;
  border: 1px solid var(--borde);
}

.dialogo-texto__titulo {
  font-size: 17px;
  font-weight: 700;
}
</style>
