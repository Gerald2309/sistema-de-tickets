<script setup>
import { ACCIONES, CONDICIONES } from '../catalogo.js'
import { MAXIMO_DE_CONDICIONES_SEGUIDAS } from '../flujo.js'

/** Los bloques que se pueden añadir. Pasado el máximo de condiciones seguidas, solo se ofrecen acciones. */
defineProps({ condicionesAntes: { type: Number, required: true } })
defineEmits(['elegir'])

const GRUPOS = /** @type {const} */ ([
  ['condicion', 'Condiciones · abren un camino Sí y uno No', CONDICIONES],
  ['accion', 'Acciones · Entonces', ACCIONES],
])
</script>

<template>
  <div>
    <template v-for="[tipo, titulo, bloques] in GRUPOS" :key="tipo">
      <h3 class="grupo">{{ titulo }}</h3>
      <p v-if="tipo === 'condicion' && condicionesAntes >= MAXIMO_DE_CONDICIONES_SEGUIDAS" class="aviso">
        Este camino ya tiene {{ MAXIMO_DE_CONDICIONES_SEGUIDAS }} condiciones seguidas. Si necesita más, cree otra
        automatización.
      </p>
      <div v-else class="bloques">
        <button
          v-for="(bloque, clave) in bloques"
          :key="clave"
          type="button"
          class="bloque"
          :class="`bloque--${tipo}`"
          @click="$emit('elegir', { tipo, clave })"
        >
          <span class="bloque__icono"><v-icon :icon="bloque.icono" size="16" /></span>
          <b>{{ bloque.titulo }}</b>
          <span class="bloque__descripcion">{{ bloque.descripcion }}</span>
        </button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.grupo {
  margin: 4px 0 10px;
  color: var(--tenue);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.aviso {
  margin: 0 0 22px;
  color: var(--suave);
  font-size: 13px;
}

.bloques {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 22px;
}

.bloque {
  --color: var(--marca-naranja);

  display: flex;
  flex-direction: column;
  gap: 6px;
  align-items: flex-start;
  padding: 12px;
  color: inherit;
  font: inherit;
  text-align: left;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  cursor: pointer;
  transition: border-color var(--rapida);
}

.bloque--accion {
  --color: var(--marca-verde-medio);
}

.bloque:hover,
.bloque:focus-visible {
  border-color: var(--acento);
}

.bloque__icono {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  color: var(--color);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.bloque b {
  font-size: 13px;
  font-weight: 600;
}

.bloque__descripcion {
  color: var(--tenue);
  font-size: 12px;
  line-height: 1.35;
}
</style>
