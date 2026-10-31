<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { reducirMovimiento } from '@/compartido/movimiento.js'

/** Número que cuenta hasta su valor (sistema de diseño, Movimiento). Con «reducir movimiento», aparece directo. */
const props = defineProps({ valor: { type: Number, required: true } })
const mostrado = ref(reducirMovimiento() ? props.valor : 0)
const formato = new Intl.NumberFormat('es-CO')
let cuadro = 0

watch(
  () => props.valor,
  (destino, origen = 0) => {
    cancelAnimationFrame(cuadro)
    if (reducirMovimiento()) {
      mostrado.value = destino
      return
    }
    const inicio = performance.now()
    const desde = origen
    const paso = (/** @type {number} */ instante) => {
      const avance = Math.min(1, (instante - inicio) / 900)
      mostrado.value = Math.round(desde + (destino - desde) * (1 - (1 - avance) ** 4))
      if (avance < 1) {
        cuadro = requestAnimationFrame(paso)
      }
    }
    cuadro = requestAnimationFrame(paso)
  },
  { immediate: true },
)

onBeforeUnmount(() => cancelAnimationFrame(cuadro))
</script>

<template>
  <span class="num" :aria-label="formato.format(valor)">{{ formato.format(mostrado) }}</span>
</template>
