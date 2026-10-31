<script setup>
import { storeToRefs } from 'pinia'
import { useNotificaciones } from '@/compartido/composables/useNotificaciones.js'
import { iconos } from '@/compartido/iconos.js'

const notificaciones = useNotificaciones()
const { lista } = storeToRefs(notificaciones)
</script>

<template>
  <TransitionGroup name="aviso" tag="div" class="pila-avisos" aria-live="polite" aria-atomic="false">
    <div v-for="aviso in lista" :key="aviso.id" class="aviso" :class="`aviso--${aviso.tipo}`" role="status">
      <span class="aviso__marca" aria-hidden="true">
        <svg v-if="aviso.tipo === 'exito'" viewBox="0 0 24 24" width="15" height="15" class="aviso__palomita">
          <path d="M5 12.5l4.5 4.5L19 7.5" />
        </svg>
        <v-icon v-else :icon="aviso.tipo === 'error' ? iconos.error : iconos.info" size="16" />
      </span>
      <div class="aviso__texto">
        <b>{{ aviso.titulo }}</b>
        <div v-if="aviso.detalle" class="aviso__detalle">{{ aviso.detalle }}</div>
      </div>
      <v-btn
        :icon="iconos.cerrar"
        variant="text"
        size="x-small"
        class="aviso__cerrar"
        aria-label="Cerrar aviso"
        @click="notificaciones.descartar(aviso.id)"
      />
      <span class="aviso__tiempo" aria-hidden="true" />
    </div>
  </TransitionGroup>
</template>

<style scoped>
.pila-avisos {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 3000;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: calc(100vw - 32px);
}

.aviso {
  position: relative;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  width: 340px;
  max-width: 100%;
  padding: 13px 10px 13px 15px;
  overflow: hidden;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  box-shadow: var(--sombra-2);
}

.aviso__marca {
  flex: none;
  display: grid;
  place-items: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  color: var(--superficie);
  background: var(--acento);
}

.aviso--exito .aviso__marca {
  background: var(--marca-verde);
}

.aviso--error .aviso__marca {
  background: var(--error);
}

.aviso__palomita path {
  fill: none;
  stroke: currentcolor;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-dasharray: 24;
  stroke-dashoffset: 24;
  animation: dibuja 0.5s 0.15s var(--curva) forwards;
}

.aviso__texto {
  flex: 1;
  min-width: 0;
}

.aviso__texto b {
  font-weight: 600;
}

.aviso__detalle {
  margin-top: 2px;
  color: var(--suave);
  font-size: 13.5px;
}

.aviso__cerrar {
  margin: -4px 0 0;
}

.aviso__tiempo {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 2px;
  background: var(--degradado);
  transform-origin: left;
  animation: cuenta-atras 3.6s linear forwards;
}

.aviso-enter-active {
  transition:
    opacity 0.5s var(--curva),
    transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.aviso-leave-active {
  transition:
    opacity 0.3s var(--curva-entrada-salida),
    transform 0.3s var(--curva-entrada-salida);
}

.aviso-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.97);
}

.aviso-leave-to {
  opacity: 0;
  transform: translateX(24px);
}
</style>
