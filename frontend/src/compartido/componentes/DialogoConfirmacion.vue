<script setup>
import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'

const confirmacion = useConfirmacion()
const { pedido } = storeToRefs(confirmacion)
const abierto = computed({
  get: () => pedido.value !== null,
  set: (valor) => !valor && confirmacion.contestar(false),
})
</script>

<template>
  <v-dialog v-model="abierto" max-width="440">
    <v-card v-if="pedido" class="confirmacion" role="alertdialog" :aria-label="pedido.titulo">
      <v-card-title class="confirmacion__titulo">{{ pedido.titulo }}</v-card-title>
      <v-card-text class="confirmacion__texto">{{ pedido.texto }}</v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="confirmacion.contestar(false)">Cancelar</v-btn>
        <v-btn :color="pedido.peligrosa ? 'error' : 'primary'" variant="flat" @click="confirmacion.contestar(true)">
          {{ pedido.accion }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
.confirmacion {
  padding: 8px;
  border: 1px solid var(--borde);
}

.confirmacion__titulo {
  font-size: 17px;
  font-weight: 700;
  white-space: normal;
}

.confirmacion__texto {
  color: var(--suave);
}
</style>
