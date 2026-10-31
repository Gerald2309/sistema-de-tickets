<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'

/** Mensaje dentro de la página: sesión expirada, sin conexión, error de carga (sistema de diseño, Avisos). */
const props = defineProps({
  tipo: {
    type: String,
    default: 'info',
    validator: (valor) => ['info', 'advertencia', 'error', 'exito'].includes(String(valor)),
  },
  titulo: { type: String, required: true },
  reintentable: { type: Boolean, default: false },
})
defineEmits(['reintentar'])

const icono = computed(
  () => ({ info: iconos.info, advertencia: iconos.porVencer, error: iconos.error, exito: iconos.exito })[props.tipo],
)
</script>

<template>
  <div class="mensaje" :class="`mensaje--${tipo}`" :role="tipo === 'error' ? 'alert' : 'status'">
    <v-icon :icon="icono" size="20" class="mensaje__icono" />
    <div class="mensaje__texto">
      <b>{{ titulo }}</b>
      <slot />
    </div>
    <v-btn v-if="reintentable" size="small" variant="outlined" class="mensaje__accion" @click="$emit('reintentar')">
      Reintentar
    </v-btn>
  </div>
</template>

<style scoped>
.mensaje {
  display: flex;
  gap: 12px;
  align-items: flex-start;
  padding: 12px 14px;
  font-size: 13.5px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-left-width: 4px;
  border-radius: var(--radio);
}

.mensaje__texto {
  flex: 1;
  min-width: 0;
}

.mensaje__texto b {
  display: block;
}

.mensaje__accion {
  background: var(--superficie);
}

.mensaje--info {
  background: var(--info-fondo);
  border-color: var(--acento-borde);
  border-left-color: var(--acento);
}

.mensaje--info .mensaje__icono {
  color: var(--acento);
}

.mensaje--advertencia {
  background: var(--advertencia-fondo);
  border-color: var(--advertencia-borde);
  border-left-color: var(--marca-naranja);
}

.mensaje--advertencia .mensaje__icono {
  color: var(--advertencia);
}

.mensaje--error {
  background: var(--error-fondo);
  border-color: var(--error-borde);
  border-left-color: var(--error);
}

.mensaje--error .mensaje__icono {
  color: var(--error);
}

.mensaje--exito {
  background: var(--exito-fondo);
  border-color: var(--exito-borde);
  border-left-color: var(--exito);
}

.mensaje--exito .mensaje__icono {
  color: var(--exito);
}
</style>
