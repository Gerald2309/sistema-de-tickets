<script setup>
import { computed } from 'vue'
import ContadorAnimado from '@/compartido/componentes/ContadorAnimado.vue'
import { iconos } from '@/compartido/iconos.js'
import { rutaDelListado } from '../filtros.js'
import { ESTADOS } from '../presentacion.js'

/**
 * Recorrido de los incidentes (RF-F13, RF-F14): cada estado es una flecha que apunta al siguiente, con su
 * conteo y, en las etapas pendientes, la parte vencida. Cada etapa abre el listado que la compone.
 */
const props = defineProps({
  resumen: { type: /** @type {import('vue').PropType<import('../tipos.js').ResumenPanel>} */ (Object), required: true },
})

/** Nota de las etapas que no vencen. */
const PIES = Object.freeze({ resuelto: 'esperan confirmación' })

const etapas = computed(() =>
  Object.entries(ESTADOS).map(([valor, presentacion]) => {
    const cantidad = props.resumen.porEstado[valor] ?? 0
    const vencidos = props.resumen.vencidosPorEstado[valor]
    return {
      valor,
      ...presentacion,
      cantidad,
      vencidos,
      proporcionVencida: vencidos !== undefined && cantidad ? vencidos / cantidad : 0,
      pie:
        PIES[/** @type {keyof typeof PIES} */ (valor)] ??
        `${props.resumen.total ? Math.round((cantidad / props.resumen.total) * 100) : 0} % del total`,
    }
  }),
)

const pendientes = computed(() => (props.resumen.porEstado.abierto ?? 0) + (props.resumen.porEstado.en_progreso ?? 0))
const porcentajeVencido = computed(() =>
  pendientes.value ? Math.round((props.resumen.vencidos / pendientes.value) * 100) : 0,
)
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-flujo">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-flujo" class="tarjeta__titulo">Recorrido de los incidentes</h2>
      <span class="nota">
        <ContadorAnimado :valor="resumen.total" /> en total · pulse una etapa para ver sus incidentes
      </span>
    </header>
    <ol class="flujo">
      <li v-for="(etapa, indice) in etapas" :key="etapa.valor" class="flujo__paso">
        <RouterLink
          :to="rutaDelListado({ estado: etapa.valor })"
          class="etapa"
          :style="{
            '--fondo-etapa': `var(--estado-${etapa.valor.replace('_', '-')}-fondo)`,
            '--texto-etapa': `var(--estado-${etapa.valor.replace('_', '-')}-texto)`,
            '--color': etapa.color,
          }"
        >
          <span class="etapa__nombre"><span class="etapa__punto" aria-hidden="true" />{{ etapa.nombre }}</span>
          <b class="etapa__cantidad"
            ><ContadorAnimado :valor="etapa.cantidad" /><span class="solo-lectores"> incidentes</span></b
          >
          <span class="pista" aria-hidden="true">
            <span class="pista__relleno" :style="{ width: `${etapa.proporcionVencida * 100}%`, '--i': indice }" />
          </span>
          <span v-if="etapa.vencidos !== undefined" class="etapa__vencidos">{{ etapa.vencidos }} vencidos</span>
          <span v-else class="etapa__pie">{{ etapa.pie }}</span>
        </RouterLink>
      </li>
    </ol>
    <footer class="flujo__pie">
      <v-icon :icon="iconos.vencido" size="16" class="flujo__icono" />
      <b class="flujo__vencidos"><ContadorAnimado :valor="resumen.vencidos" /> vencidos</b>
      <span>de {{ pendientes }} pendientes ({{ porcentajeVencido }} %) pasaron su fecha límite</span>
      <RouterLink :to="rutaDelListado({ vista: 'vencidos' })" class="enlace-sutil flujo__enlace"
        >Ver vencidos →</RouterLink
      >
    </footer>
  </section>
</template>

<style scoped>
/* La punta de cada flecha mide 22 px; cada etapa se monta 14 px sobre la anterior para encajar con ella. */
.flujo {
  display: flex;
  margin: 0;
  padding: 16px;
  list-style: none;
}

.flujo__paso {
  display: flex;
  flex: 1;
  min-width: 0;
}

.flujo__paso + .flujo__paso {
  margin-left: -14px;
}

.etapa {
  display: flex;
  flex: 1;
  flex-direction: column;
  padding: 16px 32px 14px 42px;
  color: var(--texto);
  text-decoration: none;
  background: var(--fondo-etapa);
  clip-path: polygon(0 0, calc(100% - 22px) 0, 100% 50%, calc(100% - 22px) 100%, 0 100%, 22px 50%);
  transition: filter var(--rapida);
}

.flujo__paso:first-child .etapa {
  padding-left: 20px;
  clip-path: polygon(0 0, calc(100% - 22px) 0, 100% 50%, calc(100% - 22px) 100%, 0 100%);
  border-radius: var(--radio) 0 0 var(--radio);
}

.etapa:hover {
  filter: brightness(0.97);
}

.etapa:focus-visible {
  outline: 2px solid var(--acento);
  outline-offset: -4px;
}

.etapa__nombre {
  display: flex;
  gap: 8px;
  align-items: center;
  color: var(--texto-etapa);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.etapa__punto {
  width: 8px;
  height: 8px;
  background: var(--color);
  border-radius: 50%;
}

.etapa__cantidad {
  display: block;
  margin: 4px 0 8px;
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.pista {
  display: block;
  height: 6px;
  margin-bottom: 8px;
  overflow: hidden;
  background: var(--superficie);
  border-radius: 3px;
}

.pista__relleno {
  display: block;
  height: 100%;
  background: var(--error);
  border-radius: 3px;
  transform-origin: left;
  animation: crece 1s var(--curva) both;
  animation-delay: calc(var(--i, 0) * 90ms + 0.2s);
}

.etapa__vencidos {
  color: var(--error);
  font-size: 13px;
}

.etapa__pie {
  color: var(--suave);
  font-size: 13px;
}

.flujo__pie {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  padding: 10px 18px;
  color: var(--suave);
  font-size: 13px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.flujo__icono,
.flujo__vencidos {
  color: var(--error);
}

.flujo__enlace {
  margin-left: auto;
}

/* En pantallas angostas las flechas se apilan como bloques. */
@media (width <= 760px) {
  .flujo {
    flex-direction: column;
    gap: 8px;
  }

  .flujo__paso + .flujo__paso {
    margin-left: 0;
  }

  .etapa,
  .flujo__paso:first-child .etapa {
    padding: 14px 18px;
    clip-path: none;
    border-radius: var(--radio);
  }
}
</style>
