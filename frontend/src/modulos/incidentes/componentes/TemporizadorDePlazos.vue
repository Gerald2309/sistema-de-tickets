<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { fechaHora } from '@/compartido/presentacion/fechas.js'
import { estadoDelPlazo } from '../plazos.js'

/**
 * Cuenta regresiva del acuerdo de servicio: primera respuesta y resolución, con color y texto (RNF-F08).
 * Un incidente anterior a los acuerdos puede no tener límite de primera respuesta: esa fila no se muestra.
 */
const props = defineProps({
  incidente: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente>} */ (Object), required: true },
})

const ahora = ref(new Date())
const reloj = setInterval(() => (ahora.value = new Date()), 30_000)
onBeforeUnmount(() => clearInterval(reloj))

const plazos = computed(() => {
  const { creadoEn, venceRespuestaEn, primeraRespuestaEn, fechaVencimiento, resueltoEn } = props.incidente
  const filas = [{ nombre: 'Resolución', limite: fechaVencimiento, cumplidoEn: resueltoEn }]
  if (venceRespuestaEn) {
    filas.unshift({ nombre: 'Primera respuesta', limite: venceRespuestaEn, cumplidoEn: primeraRespuestaEn })
  }
  return filas.map((fila) => ({
    ...fila,
    ...estadoDelPlazo({ inicio: creadoEn, limite: fila.limite, cumplidoEn: fila.cumplidoEn, ahora: ahora.value }),
  }))
})
</script>

<template>
  <ul class="plazos" aria-label="Plazos del acuerdo de servicio">
    <li v-for="plazo in plazos" :key="plazo.nombre" class="plazo" :class="`plazo--${plazo.estado}`">
      <span class="plazo__nombre">{{ plazo.nombre }}</span>
      <span class="plazo__texto num" :title="`Límite: ${fechaHora(plazo.limite)}`">{{ plazo.texto }}</span>
      <span class="plazo__pista" aria-hidden="true">
        <span class="plazo__relleno" :style="{ width: `${plazo.avance * 100}%` }" />
      </span>
    </li>
  </ul>
</template>

<style scoped>
.plazos {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin: 12px 0 0;
  padding: 12px 0 0;
  list-style: none;
  border-top: 1px solid var(--borde);
}

.plazo {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 4px 12px;
  font-size: 13px;
}

.plazo__nombre {
  color: var(--suave);
}

.plazo__texto {
  color: var(--color-plazo);
  font-weight: 600;
}

.plazo__pista {
  grid-column: 1 / -1;
  height: 4px;
  overflow: hidden;
  background: var(--borde);
  border-radius: 2px;
}

.plazo__relleno {
  display: block;
  height: 100%;
  background: var(--color-plazo);
  transition: width var(--media) var(--curva);
}

.plazo--a-tiempo,
.plazo--cumplido {
  --color-plazo: var(--exito-texto);
}

.plazo--cerca {
  --color-plazo: var(--advertencia);
}

.plazo--incumplido {
  --color-plazo: var(--error);
}
</style>
