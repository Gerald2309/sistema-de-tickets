<script setup>
import { computed } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { duracionAproximada } from '@/compartido/presentacion/fechas.js'
import { PRIORIDADES } from '../presentacion.js'
import InsigniaPrioridad from './InsigniaPrioridad.vue'

/**
 * Cuánto se tarda en resolver: la mediana del periodo (la mitad se resuelve antes), comparada con el periodo
 * anterior, y por prioridad con qué parte se resolvió dentro del plazo.
 */
const props = defineProps({
  resolucion: {
    type: /** @type {import('vue').PropType<import('../tipos.js').ResolucionPanel | undefined>} */ (Object),
    default: undefined,
  },
})

// Por debajo de un minuto, la diferencia con el periodo anterior es ruido.
const DIFERENCIA_MINIMA_S = 60

/** @param {number | null} proporcion */
const porcentaje = (proporcion) => (proporcion === null ? '—' : `${Math.round(proporcion * 100)} %`)

const comparacion = computed(() => {
  const mediana = props.resolucion?.mediana ?? null
  const anterior = props.resolucion?.medianaAnterior ?? null
  if (mediana === null || anterior === null || Math.abs(anterior - mediana) < DIFERENCIA_MINIMA_S) {
    return null
  }
  const mejora = mediana < anterior
  const diferencia = duracionAproximada(Math.abs(anterior - mediana), { largo: true, unidadDe: mediana })
  return { mejora, texto: `${diferencia} ${mejora ? 'menos' : 'más'} que el periodo anterior` }
})

const prioridades = computed(() =>
  Object.keys(PRIORIDADES).map((prioridad) => {
    const datos = props.resolucion?.porPrioridad[prioridad] ?? { mediana: null, aTiempo: null, cantidad: 0 }
    return {
      prioridad,
      mediana: datos.mediana === null ? '—' : duracionAproximada(datos.mediana),
      aTiempo: datos.aTiempo,
    }
  }),
)
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-resolucion">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-resolucion" class="tarjeta__titulo">Cuánto tardamos en resolver</h2>
      <span class="nota">últimos {{ resolucion?.dias ?? 30 }} días</span>
    </header>
    <template v-if="resolucion">
      <div class="resolucion">
        <template v-if="resolucion.mediana !== null">
          <p class="resolucion__cifra">
            <b class="num">{{ duracionAproximada(resolucion.mediana, { largo: true }) }}</b>
            <span
              v-if="comparacion"
              class="resolucion__comparacion"
              :class="{ 'resolucion__comparacion--mejora': comparacion.mejora }"
              >{{ comparacion.texto }}</span
            >
          </p>
          <p class="resolucion__frase">
            La mitad de los incidentes se resuelve antes de ese tiempo · <b>{{ porcentaje(resolucion.aTiempo) }}</b> se
            resolvió dentro del plazo.
          </p>
          <table class="resolucion__tabla num">
            <thead>
              <tr>
                <th scope="col">Prioridad</th>
                <th scope="col">Mediana</th>
                <th scope="col">A tiempo</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="fila in prioridades" :key="fila.prioridad">
                <td><InsigniaPrioridad :prioridad="fila.prioridad" /></td>
                <td>{{ fila.mediana }}</td>
                <td>
                  <span class="a-tiempo">
                    <span class="a-tiempo__pista" aria-hidden="true">
                      <span class="a-tiempo__relleno" :style="{ transform: `scaleX(${fila.aTiempo ?? 0})` }" />
                    </span>
                    {{ porcentaje(fila.aTiempo) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </template>
        <p v-else class="resolucion__frase">Todavía no hay incidentes resueltos en este periodo.</p>
      </div>
      <footer class="resolucion__pie">
        <span>
          <b class="num">{{ resolucion.hastaAtender === null ? '—' : duracionAproximada(resolucion.hastaAtender) }}</b>
          hasta que alguien empieza a atenderlo
        </span>
        <span><b class="num">{{ porcentaje(resolucion.devueltos) }}</b> de las soluciones devueltas por el solicitante</span>
      </footer>
    </template>
    <div v-else class="resolucion"><EsqueletoDeCarga alto="220px" /></div>
  </section>
</template>

<style scoped>
.resolucion {
  padding: 12px 16px 14px;
}

.resolucion__cifra {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: baseline;
  margin: 0;
}

.resolucion__cifra b {
  font-size: 28px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.resolucion__comparacion {
  color: var(--suave);
  font-size: 13px;
  font-weight: 600;
}

.resolucion__comparacion--mejora {
  color: var(--exito-texto);
}

.resolucion__frase {
  margin: 4px 0 12px;
  color: var(--suave);
  font-size: 13px;
}

.resolucion__frase b {
  color: var(--texto);
}

.resolucion__tabla {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.resolucion__tabla th {
  padding: 0 0 6px;
  color: var(--suave);
  font-size: 12px;
  font-weight: 500;
  text-align: left;
}

.resolucion__tabla td {
  padding: 5px 0;
}

.resolucion__tabla th:not(:first-child),
.resolucion__tabla td:not(:first-child) {
  padding-left: 12px;
}

.a-tiempo {
  display: flex;
  gap: 8px;
  align-items: center;
  justify-content: flex-end;
}

.a-tiempo__pista {
  flex: 1;
  min-width: 60px;
  height: 6px;
  overflow: hidden;
  background: var(--fondo);
  border-radius: 3px;
}

.a-tiempo__relleno {
  display: block;
  height: 100%;
  background: var(--grafico-resalte);
  border-radius: 3px;
  transform-origin: left;
}

.resolucion__pie {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 20px;
  padding: 10px 16px;
  color: var(--suave);
  font-size: 12.5px;
  background: var(--sutil);
  border-top: 1px solid var(--borde);
}

.resolucion__pie b {
  color: var(--texto);
  font-size: 13.5px;
}
</style>
