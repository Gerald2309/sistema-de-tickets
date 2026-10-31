<script setup>
import { iconos } from '@/compartido/iconos.js'

/**
 * Botón principal de un formulario: reposo → «Guardando…» → «Listo» (sistema de diseño, Botones). Mientras
 * la petición está en curso no admite un segundo envío.
 */
defineProps({
  texto: { type: String, required: true },
  textoPendiente: { type: String, default: 'Guardando…' },
  pendiente: { type: Boolean, default: false },
  exito: { type: Boolean, default: false },
  deshabilitado: { type: Boolean, default: false },
  bloque: { type: Boolean, default: false },
  tamano: { type: String, default: 'default' },
  tipo: { type: String, default: 'submit' },
  // Flecha que avanza al pasar el cursor (acción que lleva a otra pantalla, como Ingresar).
  flecha: { type: Boolean, default: false },
})
</script>

<template>
  <v-btn
    :type="tipo"
    :color="exito ? 'success' : 'primary'"
    :disabled="deshabilitado"
    :block="bloque"
    :size="tamano"
    :aria-busy="pendiente"
    :aria-disabled="pendiente || deshabilitado"
    class="boton-envio"
    @click="(/** @type {MouseEvent} */ evento) => pendiente && evento.preventDefault()"
  >
    <Transition name="texto" mode="out-in">
      <span v-if="pendiente" key="pendiente" class="boton-envio__contenido">
        <v-progress-circular indeterminate size="16" width="2" />{{ textoPendiente }}
      </span>
      <span v-else-if="exito" key="exito" class="boton-envio__contenido">
        <v-icon :icon="iconos.hecho" size="18" />Listo
      </span>
      <span v-else key="reposo" class="boton-envio__contenido">
        {{ texto }}<v-icon v-if="flecha" :icon="iconos.flecha" size="18" class="boton-envio__flecha" />
      </span>
    </Transition>
  </v-btn>
</template>

<style scoped>
.boton-envio:not(.v-btn--block) {
  min-width: 160px;
}

.boton-envio__flecha {
  transition: transform 0.25s var(--curva);
}

.boton-envio:hover .boton-envio__flecha {
  transform: translateX(3px);
}

.boton-envio__contenido {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
</style>
