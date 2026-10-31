<script setup>
import { iconos } from '@/compartido/iconos.js'
import ConfirmarSolucion from './ConfirmarSolucion.vue'

/** En el detalle: la pregunta de si la solución sirvió, debajo de la solución misma. */
defineProps({ pendiente: { type: Boolean, default: false } })
const emit = defineEmits(['confirmar'])
</script>

<template>
  <section class="tarjeta confirmar" aria-labelledby="titulo-confirmar">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-confirmar" class="tarjeta__titulo">
        <v-icon :icon="iconos.exito" size="16" class="mr-1" />¿Quedó solucionado?
      </h2>
    </header>
    <div class="tarjeta__cuerpo">
      <p class="confirmar__ayuda">Soporte marcó este incidente como resuelto. Confírmelo para cerrarlo.</p>
      <ConfirmarSolucion :pendiente="pendiente" @confirmar="(respuesta) => emit('confirmar', respuesta)" />
    </div>
  </section>
</template>

<style scoped>
.confirmar {
  border-left: 3px solid var(--estado-resuelto);
}

.confirmar__ayuda {
  margin: 0 0 12px;
  color: var(--suave);
}
</style>
