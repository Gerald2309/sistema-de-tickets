<script setup>
import { computed } from 'vue'
import ContadorAnimado from '@/compartido/componentes/ContadorAnimado.vue'
import { iconos } from '@/compartido/iconos.js'
import { rutaDelListado } from '../filtros.js'
import { ESTADOS } from '../presentacion.js'

/**
 * Flujo de incidentes por etapa (RF-F13, RF-F14): una columna por estado con su conteo, los vencidos dentro de
 * cada etapa pendiente y la franja de vencidos. Cada cifra abre el listado que la compone.
 */
const props = defineProps({
  resumen: { type: /** @type {import('vue').PropType<import('../tipos.js').ResumenPanel>} */ (Object), required: true },
})

const etapas = computed(() => {
  const mayor = Math.max(1, ...Object.values(props.resumen.porEstado))
  return Object.entries(ESTADOS).map(([valor, presentacion]) => {
    const cantidad = props.resumen.porEstado[valor] ?? 0
    return {
      valor,
      ...presentacion,
      cantidad,
      proporcion: cantidad / mayor,
      porcentaje: props.resumen.total ? Math.round((cantidad / props.resumen.total) * 100) : 0,
      vencidos: props.resumen.vencidosPorEstado[valor],
    }
  })
})

const pendientes = computed(() => (props.resumen.porEstado.abierto ?? 0) + (props.resumen.porEstado.en_progreso ?? 0))
const porcentajeVencido = computed(() =>
  pendientes.value ? Math.round((props.resumen.vencidos / pendientes.value) * 100) : 0,
)
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-flujo">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-flujo" class="tarjeta__titulo">Flujo de incidentes</h2>
      <span class="nota">
        Total <b><ContadorAnimado :valor="resumen.total" /></b> · pulse una etapa para ver sus incidentes
      </span>
    </header>
    <div class="flujo">
      <RouterLink
        v-for="(etapa, indice) in etapas"
        :key="etapa.valor"
        :to="rutaDelListado({ estado: etapa.valor })"
        class="etapa"
        :style="{ '--color': etapa.color }"
      >
        <span class="etapa__cabecera">
          <span class="etapa__punto" aria-hidden="true" />{{ etapa.nombre }}
          <v-icon v-if="indice < etapas.length - 1" :icon="iconos.siguiente" size="18" class="etapa__flecha" />
        </span>
        <b class="etapa__cantidad"
          ><ContadorAnimado :valor="etapa.cantidad" /><span class="solo-lectores"> incidentes</span></b
        >
        <span class="pista" aria-hidden="true">
          <span class="pista__relleno" :style="{ transform: `scaleX(${etapa.proporcion})`, '--i': indice }" />
        </span>
        <span v-if="etapa.vencidos !== undefined" class="etapa__vencidos">
          <v-icon :icon="iconos.vencido" size="13" />{{ etapa.vencidos }} vencidos
        </span>
        <span v-else class="etapa__pie">{{ etapa.porcentaje }} % del total</span>
      </RouterLink>
    </div>
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
.flujo {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
}

.etapa {
  display: block;
  padding: 16px 18px;
  color: var(--texto);
  text-decoration: none;
  border-top: 3px solid var(--color);
  border-right: 1px solid var(--borde);
  transition: background-color var(--rapida);
}

.etapa:last-child {
  border-right: 0;
}

.etapa:hover {
  background: var(--sutil);
}

.etapa__cabecera {
  display: flex;
  align-items: center;
  color: var(--suave);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.etapa__punto {
  width: 8px;
  height: 8px;
  margin-right: 8px;
  background: var(--color);
  border-radius: 50%;
}

.etapa__flecha {
  margin-left: auto;
  color: var(--tenue);
}

.etapa__cantidad {
  display: block;
  margin: 8px 0 10px;
  font-size: 30px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.pista {
  display: block;
  height: 6px;
  margin-bottom: 10px;
  overflow: hidden;
  background: var(--borde);
  border-radius: 3px;
}

.pista__relleno {
  display: block;
  height: 100%;
  background: var(--color);
  border-radius: 3px;
  transform-origin: left;
  animation: crece 1s var(--curva) both;
  animation-delay: calc(var(--i, 0) * 90ms + 0.2s);
}

.etapa__vencidos {
  display: inline-flex;
  gap: 4px;
  align-items: center;
  color: var(--error);
  font-size: 12px;
  font-weight: 600;
}

.etapa__pie {
  color: var(--suave);
  font-size: 12px;
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

@media (width <= 760px) {
  .flujo {
    grid-template-columns: 1fr 1fr;
  }

  .etapa:nth-child(2) {
    border-right: 0;
  }

  .etapa:nth-child(-n + 2) {
    border-bottom: 1px solid var(--borde);
  }
}
</style>
