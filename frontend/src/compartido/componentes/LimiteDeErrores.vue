<script setup>
import { onErrorCaptured, ref } from 'vue'
import MensajeEnPagina from './MensajeEnPagina.vue'

/**
 * Contiene el fallo inesperado de lo que envuelve (RNF-F25): en lugar de la pantalla rota aparece un mensaje con
 * «Reintentar», y la barra, el menú y las pestañas siguen en pie. Reintentar vuelve a montar el contenido desde
 * cero. El error no sigue subiendo; solo se muestra en la consola de desarrollo.
 */
const fallo = ref(false)
const intento = ref(0)

onErrorCaptured((error) => {
  if (import.meta.env.DEV) {
    console.error(error)
  }
  fallo.value = true
  return false
})

function reintentar() {
  fallo.value = false
  intento.value += 1
}
</script>

<template>
  <div v-if="fallo" key="fallo" class="limite-de-errores">
    <MensajeEnPagina tipo="error" titulo="Esta pantalla tuvo un problema" reintentable @reintentar="reintentar">
      El resto de la aplicación sigue funcionando. Puede reintentar o ir a otra sección desde el menú.
    </MensajeEnPagina>
  </div>
  <div v-else :key="`intento-${intento}`">
    <!-- Una clave propia, distinta de la del mensaje, para que Vue reemplace la pantalla rota en lugar de
         corregirla; la clave nueva de cada intento la vuelve a montar desde cero. -->
    <slot />
  </div>
</template>

<style scoped>
.limite-de-errores {
  padding: 24px;
}
</style>
