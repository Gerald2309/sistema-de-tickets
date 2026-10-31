<script setup>
import { computed } from 'vue'

/**
 * De un vistazo, cuánto tiene y en qué va: tres cifras y una barra apilada por estado, con su leyenda de punto
 * y texto (nunca solo color).
 */
const props = defineProps({
  totales: {
    type: /** @type {import('vue').PropType<{ abierto?: number, en_progreso?: number, resuelto?: number }>} */ (Object),
    required: true,
  },
  conRespuesta: { type: Number, default: 0 },
})

const recibidas = computed(() => props.totales.abierto ?? 0)
const enAtencion = computed(() => props.totales.en_progreso ?? 0)
const porConfirmar = computed(() => props.totales.resuelto ?? 0)
const vigentes = computed(() => recibidas.value + enAtencion.value + porConfirmar.value)

const tramos = computed(() =>
  [
    { clave: 'abierto', nombre: 'Recibidas', cantidad: recibidas.value, color: 'var(--estado-abierto)' },
    { clave: 'en_progreso', nombre: 'En atención', cantidad: enAtencion.value, color: 'var(--estado-en-progreso)' },
    { clave: 'resuelto', nombre: 'Por confirmar', cantidad: porConfirmar.value, color: 'var(--estado-resuelto)' },
  ].map((tramo) => ({ ...tramo, ancho: vigentes.value ? (tramo.cantidad / vigentes.value) * 100 : 0 })),
)
</script>

<template>
  <section v-if="vigentes > 0" class="tarjeta resumen" aria-labelledby="titulo-resumen">
    <h2 id="titulo-resumen" class="solo-lectores">Resumen de sus solicitudes</h2>
    <dl class="resumen__cifras">
      <div class="resumen__cifra">
        <dt class="nota">En curso</dt>
        <dd class="resumen__numero num">{{ recibidas + enAtencion }}</dd>
      </div>
      <div class="resumen__cifra">
        <dt class="nota">Le respondieron</dt>
        <dd class="resumen__numero resumen__numero--acento num">{{ conRespuesta }}</dd>
      </div>
      <div class="resumen__cifra">
        <dt class="nota">Por confirmar</dt>
        <dd class="resumen__numero num">{{ porConfirmar }}</dd>
      </div>
    </dl>
    <div class="resumen__barra" aria-hidden="true">
      <span
        v-for="tramo in tramos"
        :key="tramo.clave"
        class="resumen__tramo"
        :style="{ width: `${tramo.ancho}%`, background: tramo.color }"
      />
    </div>
    <ul class="resumen__leyenda">
      <li v-for="tramo in tramos" :key="tramo.clave" class="resumen__item">
        <span class="punto" :style="{ background: tramo.color }" aria-hidden="true" />{{ tramo.nombre }} ·
        <span class="num">{{ tramo.cantidad }}</span>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.resumen__cifras {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin: 0;
}

.resumen__cifra {
  display: flex;
  flex-direction: column-reverse;
  padding: 14px 16px;
  border-left: 1px solid var(--borde);
}

.resumen__cifra:first-child {
  border-left: 0;
}

.resumen__numero {
  margin: 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.resumen__numero--acento {
  color: var(--acento-texto);
}

.resumen__barra {
  display: flex;
  height: 8px;
  margin: 0 16px 14px;
  overflow: hidden;
  background: var(--resalte);
  border-radius: 4px;
}

.resumen__tramo {
  height: 100%;
}

.resumen__leyenda {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin: 0;
  padding: 0 16px 14px;
  list-style: none;
}

.resumen__item {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  font-size: 13px;
  white-space: nowrap;
}
</style>
