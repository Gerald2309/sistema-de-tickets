<script setup>
import { ref } from 'vue'
import { sumarDias } from '../../mi-dia.js'

/**
 * «Planear ▾»: hoy en el próximo hueco, mañana u otro día, sin cambiar de vista. Es la alternativa al
 * arrastre para quien usa el teclado o un solo puntero (WCAG 2.5.7).
 */
const props = defineProps({
  hoy: { type: String, required: true },
  etiqueta: { type: String, default: 'Planear' },
})
const emit = defineEmits(['elegir'])

const abierto = ref(false)
const otroDia = ref('')

/** @param {string} fecha */
function elegir(fecha) {
  abierto.value = false
  emit('elegir', fecha)
}
</script>

<template>
  <v-menu v-model="abierto" location="bottom end" :close-on-content-click="false">
    <template #activator="{ props: activador }">
      <slot name="activador" :props="activador">
        <v-btn v-bind="activador" variant="text" size="small" color="primary" class="menu-planear__boton"
          >{{ etiqueta }} ▾</v-btn
        >
      </slot>
    </template>
    <v-list density="compact" class="menu-planear">
      <v-list-item title="Hoy, en el próximo hueco" @click="elegir(props.hoy)" />
      <v-list-item title="Mañana" @click="elegir(sumarDias(props.hoy, 1))" />
      <form class="menu-planear__otro" @submit.prevent="otroDia && elegir(otroDia)">
        <label class="nota" for="otro-dia">Otro día</label>
        <input id="otro-dia" v-model="otroDia" type="date" :min="props.hoy" class="menu-planear__fecha" />
        <v-btn type="submit" size="small" variant="tonal" color="primary" :disabled="!otroDia">Elegir</v-btn>
      </form>
    </v-list>
  </v-menu>
</template>

<style scoped>
.menu-planear {
  min-width: 220px;
}

.menu-planear__boton {
  flex: none;
  white-space: nowrap;
}

.menu-planear__otro {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  padding: 6px 16px 10px;
  border-top: 1px solid var(--borde);
}

.menu-planear__otro label {
  width: 100%;
}

.menu-planear__fecha {
  flex: 1;
  min-width: 0;
  height: 30px;
  padding: 0 8px;
  color: var(--texto);
  font: inherit;
  border: 1px solid var(--borde-fuerte);
  border-radius: var(--radio);
}
</style>
