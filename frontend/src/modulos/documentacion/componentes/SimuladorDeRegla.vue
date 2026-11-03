<script setup>
import { computed, reactive } from 'vue'
import ControlSegmentado from '@/compartido/componentes/ControlSegmentado.vue'
import { PORCENTAJE, decidir } from '../simulador.js'

/**
 * Prueba de «Críticos a punto de vencer» con un incidente inventado: qué decide la consulta y qué camino toma
 * el flujo. Es la misma lógica del backend (simulador.js), no una llamada a la API.
 */
const incidente = reactive({ estado: 'abierto', prioridad: 'critica', respondido: 'no', ejecutada: 'no', consumo: 85 })

const ESTADOS = [
  { valor: 'abierto', etiqueta: 'Abierto' },
  { valor: 'en_progreso', etiqueta: 'En progreso' },
  { valor: 'resuelto', etiqueta: 'Resuelto' },
  { valor: 'cerrado', etiqueta: 'Cerrado' },
]
const PRIORIDADES = [
  { valor: 'baja', etiqueta: 'Baja' },
  { valor: 'media', etiqueta: 'Media' },
  { valor: 'alta', etiqueta: 'Alta' },
  { valor: 'critica', etiqueta: 'Crítica' },
]
const SI_NO = [
  { valor: 'no', etiqueta: 'No' },
  { valor: 'si', etiqueta: 'Sí' },
]

const decision = computed(() =>
  decidir({
    estado: /** @type {import('../simulador.js').IncidenteDePrueba['estado']} */ (incidente.estado),
    prioridad: /** @type {import('../simulador.js').IncidenteDePrueba['prioridad']} */ (incidente.prioridad),
    respondido: incidente.respondido === 'si',
    ejecutada: incidente.ejecutada === 'si',
    consumo: incidente.consumo,
  }),
)

const veredicto = computed(() => {
  const { leToca, camino, acciones } = decision.value
  if (!leToca) {
    return 'No entra en la revisión: la consulta lo deja fuera y la regla ni lo mira.'
  }
  return camino === 'si'
    ? `Entra en la próxima revisión, toma el camino «sí» y hace ${acciones.length} acciones: ${acciones.join(' y ').toLowerCase()}.`
    : 'Entra en la revisión y toma el camino «no»: no hace nada, pero la ejecución queda registrada con 0 acciones y no se vuelve a evaluar.'
})
</script>

<template>
  <div class="simulador">
    <div class="controles">
      <span class="controles__rotulo">Estado</span>
      <ControlSegmentado v-model="incidente.estado" :opciones="ESTADOS" etiqueta="Estado" />
      <span class="controles__rotulo">Prioridad</span>
      <ControlSegmentado v-model="incidente.prioridad" :opciones="PRIORIDADES" etiqueta="Prioridad" />
      <span class="controles__rotulo">¿Ya tuvo primera respuesta?</span>
      <ControlSegmentado v-model="incidente.respondido" :opciones="SI_NO" etiqueta="¿Ya tuvo primera respuesta?" />
      <span class="controles__rotulo">¿Esta regla ya actuó sobre él?</span>
      <ControlSegmentado v-model="incidente.ejecutada" :opciones="SI_NO" etiqueta="¿Esta regla ya actuó sobre él?" />
      <label for="consumo" class="controles__rotulo">
        Plazo de respuesta consumido: <b class="num">{{ incidente.consumo }} %</b>
      </label>
      <input
        id="consumo"
        v-model.number="incidente.consumo"
        type="range"
        min="0"
        max="120"
        class="controles__deslizador"
      />
    </div>

    <div class="resultado" aria-live="polite">
      <p class="resultado__grupo">1 · IncidentesPorDisparar (SQL)</p>
      <div
        v-for="{ condicion, cumple } in decision.consulta"
        :key="condicion"
        class="condicion"
        :class="cumple ? 'condicion--cumple' : 'condicion--falla'"
      >
        <code>{{ condicion }}</code
        ><span>{{ cumple ? '✓' : '✗' }}</span>
      </div>
      <p class="resultado__grupo">2 · Flujo (dominio)</p>
      <div
        class="condicion"
        :class="
          !decision.camino ? 'condicion--fuera' : decision.camino === 'si' ? 'condicion--cumple' : 'condicion--falla'
        "
      >
        <code>prioridad es_una_de [critica]</code
        ><span>{{ decision.camino === 'si' ? 'sí' : (decision.camino ?? '—') }}</span>
      </div>
      <p class="veredicto" :class="decision.leToca ? 'veredicto--entra' : 'veredicto--fuera'">{{ veredicto }}</p>
      <p class="resultado__pie">
        Regla: disparador <code>sla_por_vencer</code> al {{ PORCENTAJE }} % del plazo de respuesta.
      </p>
    </div>
  </div>
</template>

<style scoped>
.simulador {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.controles {
  display: grid;
  gap: 8px;
  align-content: start;
  justify-items: start;
  padding: 16px;
  border-right: 1px solid var(--borde);
}

.controles__rotulo {
  margin-top: 6px;
  color: var(--suave);
  font-size: 13px;
}

.controles__deslizador {
  width: 100%;
  accent-color: var(--acento);
}

.resultado {
  padding: 8px 0;
}

.resultado__grupo {
  margin: 0;
  padding: 10px 16px 6px;
  color: var(--tenue);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.condicion {
  display: flex;
  gap: 12px;
  justify-content: space-between;
  padding: 8px 16px;
  font-size: 13px;
  border-top: 1px solid var(--borde);
}

.condicion code {
  font: 12px var(--mono);
}

.condicion span {
  font-weight: 700;
}

.condicion--cumple span {
  color: var(--exito-texto);
}

.condicion--falla span {
  color: var(--error);
}

.condicion--fuera {
  opacity: 0.45;
}

.veredicto {
  margin: 12px 16px 8px;
  padding: 12px 14px;
  font-size: 14px;
  border-radius: var(--radio);
}

.veredicto--entra {
  color: var(--exito-texto);
  background: var(--exito-fondo);
  border: 1px solid var(--exito-borde);
}

.veredicto--fuera {
  color: var(--estado-cerrado-texto);
  background: var(--estado-cerrado-fondo);
  border: 1px solid var(--borde);
}

.resultado__pie {
  margin: 0;
  padding: 0 16px 8px;
  color: var(--tenue);
  font-size: 12px;
}

@media (width <= 900px) {
  .simulador {
    grid-template-columns: 1fr;
  }

  .controles {
    border-right: 0;
    border-bottom: 1px solid var(--borde);
  }
}
</style>
