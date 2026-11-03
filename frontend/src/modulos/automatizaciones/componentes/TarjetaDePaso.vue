<script setup>
import { iconos } from '@/compartido/iconos.js'

/** Un bloque del flujo: el tipo se distingue por el ícono y una línea fina de color, sin fondos. */
defineProps({
  tipo: { type: /** @type {import('vue').PropType<'cuando' | 'si' | 'entonces'>} */ (String), required: true },
  icono: { type: String, required: true },
  titulo: { type: String, required: true },
  texto: { type: String, required: true },
  seleccionada: { type: Boolean, default: false },
  conError: { type: Boolean, default: false },
  quitable: { type: Boolean, default: true },
})
defineEmits(['elegir', 'quitar'])

/** @type {Record<string, string>} */
const NOMBRE = { cuando: 'Cuándo', si: 'Si', entonces: 'Entonces' }
</script>

<template>
  <div class="paso" :class="[`paso--${tipo}`, { 'paso--elegido': seleccionada, 'paso--error': conError }]">
    <button type="button" class="paso__principal" :aria-pressed="seleccionada" @click="$emit('elegir')">
      <span class="paso__icono"><v-icon :icon="icono" size="19" /></span>
      <span class="paso__textos">
        <span class="paso__tipo">{{ NOMBRE[tipo] }}</span>
        <b class="paso__titulo">{{ titulo }}</b>
        <span class="paso__texto">{{ texto }}</span>
      </span>
      <v-icon v-if="conError" :icon="iconos.error" size="18" class="paso__alerta" aria-label="Tiene un error" />
    </button>
    <v-btn
      v-if="quitable"
      :icon="iconos.eliminar"
      variant="text"
      size="small"
      class="paso__quitar"
      :aria-label="`Quitar ${titulo}`"
      @click="$emit('quitar')"
    />
  </div>
</template>

<style scoped>
/* Los tres tipos con los colores de la marca: cian (cuándo), naranja (si) y verde (entonces). */
.paso {
  --color: var(--marca-cian);

  position: relative;
  display: flex;
  align-items: center;
  width: 290px;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
  transition:
    border-color var(--rapida),
    box-shadow var(--rapida);
}

.paso::before {
  position: absolute;
  top: 12px;
  bottom: 12px;
  left: -1px;
  width: 3px;
  background: var(--color);
  border-radius: 0 2px 2px 0;
  content: '';
}

.paso--si {
  --color: var(--marca-naranja);
}

.paso--entonces {
  --color: var(--marca-verde-medio);
}

.paso:hover {
  border-color: var(--borde-fuerte);
}

.paso--elegido {
  border-color: var(--acento);
  box-shadow: 0 0 0 3px var(--acento-fondo);
}

.paso--error {
  border-color: var(--error);
}

.paso__principal {
  display: flex;
  flex: 1;
  gap: 12px;
  align-items: center;
  min-width: 0;
  padding: 14px 6px 14px 16px;
  color: inherit;
  font: inherit;
  text-align: left;
  background: none;
  border: 0;
  border-radius: inherit;
  cursor: pointer;
}

.paso__icono {
  display: grid;
  flex: none;
  place-items: center;
  width: 36px;
  height: 36px;
  color: var(--color);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}

.paso__textos {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.paso__tipo {
  color: var(--tenue);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.paso__titulo {
  font-size: 14.5px;
  font-weight: 600;
}

.paso__texto {
  overflow-wrap: anywhere;
  color: var(--suave);
  font-size: 13px;
}

.paso__alerta {
  color: var(--error);
}

.paso__quitar {
  margin-right: 4px;
  color: var(--tenue);
  opacity: 0;
  transition: opacity var(--rapida);
}

.paso:hover .paso__quitar,
.paso--elegido .paso__quitar,
.paso__quitar:focus-visible {
  opacity: 1;
}
</style>
