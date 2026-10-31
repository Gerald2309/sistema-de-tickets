<script setup>
/**
 * Pares etiqueta y valor alineados, como el panel lateral de un ticket en Jira (sistema de diseño, Tarjetas).
 * Cada valor puede reemplazarse con la ranura `valor-<indice>`.
 */
/** @typedef {{ etiqueta: string, valor?: string | number | null, tenue?: boolean }} Propiedad */

defineProps({
  elementos: { type: /** @type {import('vue').PropType<Propiedad[]>} */ (Array), required: true },
})
</script>

<template>
  <dl class="propiedades">
    <div v-for="(elemento, indice) in elementos" :key="elemento.etiqueta" class="propiedad">
      <dt class="propiedad__etiqueta">{{ elemento.etiqueta }}</dt>
      <dd class="propiedad__valor" :class="{ 'propiedad__valor--tenue': elemento.tenue }">
        <slot :name="`valor-${indice}`">{{ elemento.valor }}</slot>
      </dd>
    </div>
  </dl>
</template>

<style scoped>
.propiedades {
  margin: 0;
}

.propiedad {
  display: flex;
  gap: 16px;
  justify-content: space-between;
  padding: 11px 0;
  font-size: 13.5px;
  border-bottom: 1px solid var(--borde);
}

.propiedad:first-child {
  padding-top: 0;
}

.propiedad:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.propiedad__etiqueta {
  color: var(--suave);
}

.propiedad__valor {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

.propiedad__valor--tenue {
  color: var(--suave);
  font-weight: 400;
}
</style>
