<script setup>
import { computed } from 'vue'

/**
 * Pie de una tabla paginada: «Mostrando a–b de N», la hora de la última actualización, el tamaño de página y
 * la paginación (sistema de diseño: el total y la actualización van aquí, no en la barra de herramientas).
 */
const props = defineProps({
  pagina: { type: Number, required: true },
  ultimaPagina: { type: Number, required: true },
  porPagina: { type: Number, required: true },
  total: { type: Number, required: true },
  enPagina: { type: Number, required: true },
  actualizado: { type: String, default: '' },
  enVivo: { type: Boolean, default: false },
  tamanos: { type: /** @type {import('vue').PropType<readonly number[]>} */ (Array), default: () => [15, 25, 50, 100] },
})
defineEmits(['update:pagina', 'update:porPagina'])

const desde = computed(() => (props.total === 0 ? 0 : (props.pagina - 1) * props.porPagina + 1))
const hasta = computed(() => (props.total === 0 ? 0 : desde.value + props.enPagina - 1))
const formato = new Intl.NumberFormat('es-CO')
</script>

<template>
  <footer class="pie">
    <span class="pie__resumen">
      Mostrando <span class="num">{{ desde }}–{{ hasta }}</span> de <span class="num">{{ formato.format(total) }}</span>
      <span v-if="actualizado" class="pie__vivo">
        <span class="pie__punto" :class="{ 'pie__punto--en-vivo': enVivo }" aria-hidden="true" />{{ actualizado }}
      </span>
    </span>
    <span class="pie__controles">
      <label v-if="tamanos.length > 1" class="pie__tamano">
        <span class="d-none d-sm-inline">Filas por página</span>
        <select
          :value="porPagina"
          @change="$emit('update:porPagina', Number(/** @type {HTMLSelectElement} */ ($event.target).value))"
        >
          <option v-for="tamano in tamanos" :key="tamano" :value="tamano">{{ tamano }}</option>
        </select>
      </label>
      <v-pagination
        :model-value="pagina"
        :length="Math.max(1, ultimaPagina)"
        :total-visible="5"
        density="comfortable"
        size="small"
        active-color="primary"
        aria-label="Paginación"
        @update:model-value="$emit('update:pagina', $event)"
      />
    </span>
  </footer>
</template>

<style scoped>
.pie {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  justify-content: space-between;
  padding: 8px 16px;
  color: var(--suave);
  font-size: 13px;
  border-top: 1px solid var(--borde);
}

.pie__vivo {
  display: inline-flex;
  align-items: center;
  margin-left: 10px;
}

.pie__punto {
  width: 7px;
  height: 7px;
  margin-right: 7px;
  background: var(--tenue);
  border-radius: 50%;
}

.pie__punto--en-vivo {
  background: var(--marca-verde);
  animation: pulso 2s infinite;
}

.pie__controles {
  display: flex;
  gap: 12px;
  align-items: center;
}

.pie__tamano {
  display: flex;
  gap: 8px;
  align-items: center;
}

.pie__tamano select {
  padding: 2px 4px;
  color: var(--texto);
  font: inherit;
  background: var(--superficie);
  border: 1px solid var(--borde);
  border-radius: var(--radio);
}
</style>
