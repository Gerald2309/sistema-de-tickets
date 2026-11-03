<script setup>
import { ACCIONES, describirCamino } from '../catalogo.js'

/** Lo que habrían hecho los pasos con los incidentes recientes, por camino, con algunos ejemplos. */
defineProps({
  resultado: {
    type: /** @type {import('vue').PropType<import('../tipos.js').ResultadoDePrueba>} */ (Object),
    required: true,
  },
})

/** @param {string[]} acciones */
const nombresDe = (acciones) =>
  acciones.map((accion) => ACCIONES[accion]?.titulo ?? accion).join(', ') || 'Ninguna acción'
</script>

<template>
  <div>
    <!-- Lo que haría ya la próxima revisión: responde «¿por qué no ha avisado todavía?». -->
    <div v-if="resultado.ahora" class="ahora">
      <p class="rotulo">Ahora mismo, según el disparador</p>
      <p v-if="resultado.ahora.lesToca" class="ahora__texto">
        Le toca a <b>{{ resultado.ahora.lesToca }}</b> {{ resultado.ahora.lesToca === 1 ? 'incidente' : 'incidentes' }};
        <b>{{ resultado.ahora.conAcciones }}</b> cumplirían las condiciones y recibirían acciones.
      </p>
      <p v-else class="ahora__texto">
        Ningún incidente cumple todavía el disparador. Cuando alguno lo cumpla, la regla actuará en la siguiente
        revisión, cada cinco minutos.
      </p>
    </div>
    <p v-if="!resultado.evaluados" class="nota">No hay incidentes creados en los últimos {{ resultado.dias }} días.</p>
    <template v-else>
      <p class="rotulo">Habría hecho algo con</p>
      <p class="cifra">
        {{ resultado.conAcciones }}
        <small>de {{ resultado.evaluados }} incidentes de los últimos {{ resultado.dias }} días</small>
      </p>

      <h3 class="rotulo seccion">Por dónde salió cada uno</h3>
      <ul class="caminos">
        <li v-for="(camino, i) in resultado.caminos" :key="i" class="camino">
          <div class="camino__cabeza">
            <span>{{ describirCamino(camino.decisiones) }}</span>
            <b>{{ camino.cantidad }}</b>
          </div>
          <div class="medidor" aria-hidden="true">
            <span :style="{ width: `${(camino.cantidad / resultado.evaluados) * 100}%` }" />
          </div>
          <span class="nota">{{ nombresDe(camino.acciones) }}</span>
        </li>
      </ul>

      <template v-if="resultado.ejemplos.length">
        <h3 class="rotulo seccion">Algunos que habrían recibido acciones</h3>
        <ul class="ejemplos">
          <li v-for="ejemplo in resultado.ejemplos" :key="ejemplo.id">
            <RouterLink :to="{ name: 'incidente', params: { id: ejemplo.id } }">{{ ejemplo.codigo }}</RouterLink>
            {{ ejemplo.titulo }}
          </li>
        </ul>
      </template>
    </template>
    <p class="nota seccion">Se evalúan las condiciones, no el disparador, y no se cambia nada.</p>
  </div>
</template>

<style scoped>
.ahora {
  margin-bottom: 24px;
  padding: 12px 14px;
  background: var(--acento-fondo);
  border-left: 3px solid var(--acento);
  border-radius: var(--radio);
}

.ahora__texto {
  margin: 0;
  font-size: 13.5px;
}

.rotulo {
  margin: 0 0 4px;
  color: var(--tenue);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.cifra {
  margin: 0;
  font-size: 30px;
  font-weight: 700;
  line-height: 1.15;
}

.cifra small {
  color: var(--suave);
  font-size: 13.5px;
  font-weight: 500;
}

.caminos,
.ejemplos {
  margin: 0;
  padding: 0;
  list-style: none;
}

.camino {
  margin-bottom: 12px;
}

.camino__cabeza {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
  font-size: 13px;
}

.medidor {
  height: 6px;
  overflow: hidden;
  background: var(--fondo);
  border-radius: 3px;
}

.medidor span {
  display: block;
  height: 100%;
  background: var(--acento);
}

.seccion {
  margin-top: 24px;
}

.ejemplos a {
  color: var(--acento-texto);
  font-weight: 600;
  text-decoration: none;
}

.ejemplos a:hover {
  text-decoration: underline;
}

.ejemplos li {
  padding: 8px 0;
  font-size: 13px;
  border-bottom: 1px solid var(--borde);
}
</style>
