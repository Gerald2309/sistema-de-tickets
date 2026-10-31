<script setup>
import { computed } from 'vue'
import { iconos } from '@/compartido/iconos.js'
import { fechaHora } from '@/compartido/presentacion/fechas.js'

/**
 * El camino de la solicitud hasta su resolución, contado para quien la reportó: el tramo recorrido es una línea
 * continua, lo que falta es punteado y el paso actual marca «está aquí». Cada paso dice lo que le importa a esa
 * persona (quién la atiende, para cuándo se espera respuesta) sin datos internos.
 */
const props = defineProps({
  incidente: { type: /** @type {import('vue').PropType<import('../tipos.js').Incidente>} */ (Object), required: true },
})

const ORDEN_DE_ESTADOS = ['abierto', 'en_progreso', 'resuelto', 'cerrado']

/** @returns {string} la fecha estimada, o que ya pasó: una fecha vencida no se presenta como promesa */
function respuestaEstimada() {
  const { fechaVencimiento, vencido } = props.incidente
  return vencido
    ? 'La fecha estimada ya pasó; soporte la tiene presente.'
    : `Respuesta estimada: ${fechaHora(fechaVencimiento)}`
}

const pasos = computed(() => {
  const { estado, asignado, creadoEn } = props.incidente
  const alcanzado = ORDEN_DE_ESTADOS.indexOf(estado)
  const resuelta = alcanzado >= 2
  return [
    { titulo: 'Recibida', detalle: fechaHora(creadoEn), hecho: true },
    {
      titulo: asignado || alcanzado >= 1 ? 'En atención' : 'Esperando a soporte',
      detalle: [
        asignado ? `La atiende ${asignado.nombre}.` : 'Pronto alguien de soporte la tomará.',
        ...(resuelta ? [] : [respuestaEstimada()]),
      ].join(' '),
      hecho: asignado !== null || alcanzado >= 1,
    },
    { titulo: 'Resuelta', detalle: 'Soporte aplicó una solución; usted confirma si sirvió.', hecho: resuelta },
    { titulo: 'Cerrada', detalle: 'Usted confirmó que quedó solucionada.', hecho: alcanzado >= 3 },
  ]
})
// «Está aquí»: el último paso alcanzado.
const actual = computed(() => {
  const pendiente = pasos.value.findIndex((paso) => !paso.hecho)
  return pendiente === -1 ? pasos.value.length - 1 : pendiente - 1
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-progreso">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-progreso" class="tarjeta__titulo">Su solicitud</h2>
      <span class="nota">Paso {{ actual + 1 }} de {{ pasos.length }}</span>
    </header>
    <ol class="tarjeta__cuerpo ruta">
      <li
        v-for="(paso, indice) in pasos"
        :key="paso.titulo"
        class="ruta__paso"
        :class="{
          'ruta__paso--hecho': paso.hecho,
          'ruta__paso--actual': indice === actual,
          'ruta__paso--recorrido': indice < actual,
        }"
        :aria-current="indice === actual ? 'step' : undefined"
      >
        <span class="ruta__marca" aria-hidden="true">
          <v-icon v-if="paso.hecho && indice !== actual" :icon="iconos.exito" size="14" />
        </span>
        <div class="ruta__texto">
          <b class="ruta__titulo">
            {{ paso.titulo }}
            <span v-if="indice === actual && indice < pasos.length - 1" class="ruta__aqui">Está aquí</span>
          </b>
          <span v-if="paso.hecho || indice === actual + 1" class="ruta__detalle">{{ paso.detalle }}</span>
        </div>
      </li>
    </ol>
  </section>
</template>

<style scoped>
.ruta {
  margin: 0;
  list-style: none;
}

.ruta__paso {
  position: relative;
  display: flex;
  gap: 12px;
  padding-bottom: 18px;
  color: var(--tenue);
}

.ruta__paso:last-child {
  padding-bottom: 0;
}

/* El camino: une cada marca con la siguiente. Punteado lo que falta, continuo lo recorrido. */
.ruta__paso:not(:last-child)::before {
  position: absolute;
  top: 22px;
  bottom: 0;
  left: 10px;
  border-left: 2px dashed var(--borde-fuerte);
  content: '';
}

.ruta__paso--recorrido:not(:last-child)::before {
  border-left-style: solid;
  border-left-color: var(--estado-resuelto);
  transform-origin: top;
  animation: ruta-crece var(--media) var(--curva) both;
}

.ruta__paso--hecho,
.ruta__paso--actual {
  color: var(--texto);
}

.ruta__marca {
  position: relative;
  z-index: 1;
  display: inline-flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 22px;
  height: 22px;
  color: var(--superficie);
  background: var(--superficie);
  border: 2px solid var(--borde-fuerte);
  border-radius: 50%;
}

.ruta__paso--hecho .ruta__marca {
  background: var(--estado-resuelto);
  border-color: var(--estado-resuelto);
}

/* Está aquí: anillo de acento y un punto que late suave. */
.ruta__paso--actual .ruta__marca {
  background: var(--superficie);
  border-color: var(--acento);
}

.ruta__paso--actual .ruta__marca::after {
  width: 8px;
  height: 8px;
  background: var(--acento);
  border-radius: 50%;
  content: '';
  animation: ruta-late 1.6s var(--curva-entrada-salida) infinite;
}

.ruta__paso--actual.ruta__paso--hecho:last-child .ruta__marca {
  background: var(--estado-resuelto);
  border-color: var(--estado-resuelto);
}

.ruta__paso--actual.ruta__paso--hecho:last-child .ruta__marca::after {
  display: none;
}

.ruta__texto {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
  padding-top: 1px;
}

.ruta__titulo {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 13.5px;
}

.ruta__aqui {
  color: var(--acento);
  font-size: 11.5px;
  font-weight: 600;
}

.ruta__detalle {
  color: var(--suave);
  font-size: 12.5px;
  line-height: 1.5;
}

@keyframes ruta-crece {
  from {
    transform: scaleY(0);
  }
}

@keyframes ruta-late {
  50% {
    opacity: 0.35;
    transform: scale(0.7);
  }
}

@media (prefers-reduced-motion: reduce) {
  .ruta__paso--recorrido::before,
  .ruta__paso--actual .ruta__marca::after {
    animation: none;
  }
}
</style>
