<script setup>
import { computed } from 'vue'
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import { rutaDelListado } from '../filtros.js'

/**
 * Cuánto llevan esperando los pendientes, por tramos de antigüedad. Cada barra lleva su cifra escrita; la
 * más alta se destaca y la frase dice qué conviene revisar.
 */
const props = defineProps({
  espera: {
    type: /** @type {import('vue').PropType<import('../tipos.js').IndicadoresPanel['espera'] | undefined>} */ (Array),
    default: undefined,
  },
})

/** @type {Readonly<Record<string, string>>} */
const NOMBRES = Object.freeze({ 1: 'menos de 1 día', 3: '1 a 3 días', 7: '3 a 7 días', 28: '1 a 4 semanas' })
const SEMANA = 7

const tramos = computed(() => {
  const mayor = Math.max(1, ...(props.espera ?? []).map((tramo) => tramo.cantidad))
  return (props.espera ?? []).map((tramo) => ({
    nombre: tramo.hastaDias === null ? 'más de 4 semanas' : NOMBRES[tramo.hastaDias],
    cantidad: tramo.cantidad,
    alto: `${Math.max(4, (tramo.cantidad / mayor) * 100)}%`,
    resalte: tramo.cantidad === mayor,
  }))
})

const frase = computed(() => {
  const espera = props.espera ?? []
  const total = espera.reduce((suma, tramo) => suma + tramo.cantidad, 0)
  if (total === 0) {
    return 'No hay incidentes pendientes.'
  }
  // Los tramos que empiezan después de una semana: «1 a 4 semanas» y «más de 4 semanas».
  const viejos = espera
    .filter((_, indice) => indice > 0 && (espera[indice - 1].hastaDias ?? 0) >= SEMANA)
    .reduce((suma, tramo) => suma + tramo.cantidad, 0)
  return viejos * 2 > total
    ? 'La mayoría lleva más de una semana: conviene revisar los más antiguos antes que los nuevos.'
    : 'La mayoría se reportó en la última semana.'
})
</script>

<template>
  <section class="tarjeta" aria-labelledby="titulo-espera">
    <header class="tarjeta__cabecera">
      <h2 id="titulo-espera" class="tarjeta__titulo">Cuánto llevan esperando</h2>
      <RouterLink :to="rutaDelListado({ vista: 'vencidos', orden: 'creado', dir: 'asc' })" class="enlace-sutil">
        Ver los vencidos más antiguos
      </RouterLink>
    </header>
    <div class="espera">
      <template v-if="espera">
        <p class="espera__frase">{{ frase }}</p>
        <ul class="espera__barras">
          <li v-for="tramo in tramos" :key="tramo.nombre" class="espera__tramo">
            <span class="espera__columna">
              <b class="espera__cifra num" :class="{ 'espera__cifra--resalte': tramo.resalte }">{{ tramo.cantidad }}</b>
              <span
                class="espera__barra"
                :class="{ 'espera__barra--resalte': tramo.resalte }"
                :style="{ height: tramo.alto }"
                aria-hidden="true"
              />
            </span>
            <span class="espera__nombre">{{ tramo.nombre }}</span>
          </li>
        </ul>
      </template>
      <EsqueletoDeCarga v-else alto="170px" />
    </div>
  </section>
</template>

<style scoped>
.espera {
  padding: 12px 16px 14px;
}

.espera__frase {
  margin: 0 0 14px;
  color: var(--suave);
  font-size: 13px;
}

.espera__barras {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 10px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.espera__columna {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: center;
  justify-content: flex-end;
  height: 140px;
  border-bottom: 1px solid var(--borde);
}

.espera__cifra {
  color: var(--suave);
  font-size: 12.5px;
  font-weight: 500;
}

.espera__cifra--resalte {
  color: var(--texto);
  font-weight: 700;
}

.espera__barra {
  width: 100%;
  background: var(--grafico-barra);
  border-radius: var(--radio) var(--radio) 0 0;
  transform-origin: bottom;
  animation: sube 0.8s var(--curva) both;
}

.espera__barra--resalte {
  background: var(--grafico-resalte);
}

.espera__nombre {
  display: block;
  margin-top: 6px;
  color: var(--suave);
  font-size: 12px;
  text-align: center;
}

@keyframes sube {
  from {
    transform: scaleY(0);
  }
}
</style>
