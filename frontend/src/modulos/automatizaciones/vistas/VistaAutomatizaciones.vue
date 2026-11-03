<script setup>
import EsqueletoDeCarga from '@/compartido/componentes/EsqueletoDeCarga.vue'
import EstadoVacio from '@/compartido/componentes/EstadoVacio.vue'
import InterruptorCompacto from '@/compartido/componentes/InterruptorCompacto.vue'
import MensajeEnPagina from '@/compartido/componentes/MensajeEnPagina.vue'
import { useConfirmacion } from '@/compartido/composables/useConfirmacion.js'
import { iconos } from '@/compartido/iconos.js'
import { tiempoRelativo } from '@/compartido/presentacion/fechas.js'
import { DISPARADORES, describirDisparo } from '../catalogo.js'
import { useAutomatizaciones } from '../composables/useAutomatizaciones.js'

/** @typedef {import('../tipos.js').Automatizacion} Automatizacion */

const { lista, alternar, eliminar } = useAutomatizaciones()
const confirmacion = useConfirmacion()

/** @param {Automatizacion} automatizacion */
function uso(automatizacion) {
  if (!automatizacion.aplicaciones) {
    return 'Todavía no se ha aplicado'
  }
  const veces = automatizacion.aplicaciones === 1 ? '1 vez' : `${automatizacion.aplicaciones} veces`
  return automatizacion.ultimaAplicacionEn
    ? `${veces} · ${tiempoRelativo(automatizacion.ultimaAplicacionEn).texto}`
    : veces
}

/** @param {Automatizacion} automatizacion */
async function confirmarEliminar(automatizacion) {
  const confirmado = await confirmacion.pedir({
    titulo: `¿Eliminar «${automatizacion.nombre}»?`,
    texto: 'Deja de aplicarse. Lo que ya hizo sigue en el historial de cada incidente.',
    accion: 'Eliminar',
    peligrosa: true,
  })
  if (confirmado) {
    await eliminar(automatizacion).catch(() => {})
  }
}
</script>

<template>
  <div class="pagina">
    <div class="migas entra">Administración</div>
    <header class="cabecera">
      <div>
        <h1 v-revelar class="titulo-pagina">Automatizaciones</h1>
        <p class="subtitulo entra" style="--i: 2">
          Reglas que actúan sobre los incidentes al crearse o al pasar un tiempo. Lo que hacen queda a nombre de quien
          las guardó.
        </p>
      </div>
      <v-btn
        color="primary"
        variant="flat"
        class="entra"
        style="--i: 2"
        :prepend-icon="iconos.crear"
        :to="{ name: 'nueva-automatizacion' }"
      >
        Nueva automatización
      </v-btn>
    </header>

    <MensajeEnPagina
      v-if="lista.isError.value"
      tipo="error"
      titulo="No se pudo cargar la lista"
      reintentable
      class="mb-3"
      @reintentar="lista.refetch()"
    >
      {{ lista.error.value?.message }}
    </MensajeEnPagina>

    <div class="tarjeta entra" style="--i: 3">
      <div v-if="lista.data.value && !lista.data.value.length" class="vacio">
        <EstadoVacio
          :icono="iconos.automatizaciones"
          titulo="Todavía no hay automatizaciones"
          texto="Por ejemplo: asignar los incidentes de red de una empresa, o cerrar los resueltos que nadie confirma."
        />
      </div>
      <v-table v-else v-desplazable="'Automatizaciones'" density="compact">
        <thead>
          <tr>
            <th>Automatización</th>
            <th>Cuándo</th>
            <th>Uso</th>
            <th>A nombre de</th>
            <th>Activa</th>
            <th class="columna-acciones"><span class="solo-lectores">Acciones</span></th>
          </tr>
        </thead>
        <tbody v-if="!lista.data.value">
          <tr v-for="fila in 4" :key="fila">
            <td><EsqueletoDeCarga :ancho="`${140 + fila * 16}px`" /></td>
            <td><EsqueletoDeCarga ancho="160px" /></td>
            <td><EsqueletoDeCarga ancho="90px" /></td>
            <td><EsqueletoDeCarga ancho="100px" /></td>
            <td><EsqueletoDeCarga ancho="32px" /></td>
            <td />
          </tr>
        </tbody>
        <TransitionGroup v-else name="fila" tag="tbody">
          <tr v-for="automatizacion in lista.data.value" :key="automatizacion.id">
            <td>
              <RouterLink :to="{ name: 'automatizacion', params: { id: automatizacion.id } }" class="nombre">
                {{ automatizacion.nombre }}
              </RouterLink>
            </td>
            <td>
              {{ DISPARADORES[automatizacion.disparo.disparador]?.nombre }}
              <small v-if="automatizacion.disparo.disparador !== 'creacion'" class="nota d-block">
                {{ describirDisparo(automatizacion.disparo) }}
              </small>
            </td>
            <td class="text-no-wrap">
              {{ uso(automatizacion) }}
              <small v-if="automatizacion.aplicacionesConError" class="con-error d-block">
                {{ automatizacion.aplicacionesConError }} se detuvieron por un error
              </small>
            </td>
            <td>{{ automatizacion.autor.nombre }}</td>
            <td>
              <InterruptorCompacto
                :model-value="automatizacion.activa"
                :etiqueta="`Activa: ${automatizacion.nombre}`"
                @update:model-value="alternar(automatizacion)"
              />
            </td>
            <td class="columna-acciones">
              <v-btn
                :icon="iconos.eliminar"
                variant="text"
                size="small"
                :aria-label="`Eliminar ${automatizacion.nombre}`"
                @click="confirmarEliminar(automatizacion)"
              />
            </td>
          </tr>
        </TransitionGroup>
      </v-table>
    </div>
  </div>
</template>

<style scoped>
.vacio {
  padding: 32px 16px;
}

.nombre {
  color: var(--acento-texto);
  font-weight: 600;
  text-decoration: none;
}

.nombre:hover {
  text-decoration: underline;
}

.con-error {
  color: var(--error);
}
</style>
