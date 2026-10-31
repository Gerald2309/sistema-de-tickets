<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'

/** Aviso persistente mientras no hay conexión (RNF-F24). Al volver, las consultas se reanudan solas. */
const sinConexion = ref(typeof navigator !== 'undefined' && navigator.onLine === false)
const alCambiar = () => (sinConexion.value = !navigator.onLine)

onMounted(() => {
  window.addEventListener('online', alCambiar)
  window.addEventListener('offline', alCambiar)
})
onBeforeUnmount(() => {
  window.removeEventListener('online', alCambiar)
  window.removeEventListener('offline', alCambiar)
})
</script>

<template>
  <Transition name="texto">
    <div v-if="sinConexion" class="sin-conexion">
      <MensajeEnPagina tipo="advertencia" titulo="Sin conexión">
        Lo que escriba no se pierde; los datos se actualizarán al volver la conexión.
      </MensajeEnPagina>
    </div>
  </Transition>
</template>

<style scoped>
.sin-conexion {
  max-width: 1280px;
  margin: 16px auto 0;
  padding: 0 28px;
}
</style>
